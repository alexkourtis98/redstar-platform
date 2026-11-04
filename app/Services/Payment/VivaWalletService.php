<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentGatewayLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class VivaWalletService
{
    protected string $baseUrl;
    protected string $merchantId;
    protected string $apiKey;
    protected string $sourceCode;

    public function __construct()
    {
        $this->baseUrl = config('services.viva.environment') === 'production'
            ? 'https://api.vivapayments.com'
            : 'https://demo-api.vivapayments.com';

        $this->merchantId = config('services.viva.merchant_id');
        $this->apiKey = config('services.viva.api_key');
        $this->sourceCode = config('services.viva.source_code');
    }

    public function createPaymentOrder(Payment $payment, string $successUrl, string $failureUrl): array
    {
        $startTime = microtime(true);

        $payload = [
            'amount' => (int) round($payment->amount * 100), // Convert to cents
            'customerTrns' => $payment->project?->title ?? "Payment #{$payment->id}",
            'customer' => [
                'email' => $payment->client?->email,
                'fullName' => $payment->client?->name,
            ],
            'paymentTimeout' => 1800, // 30 minutes
            'preauth' => false,
            'allowRecurring' => false,
            'maxInstallments' => 0,
            'paymentNotification' => true,
            'tipAmount' => 0,
            'disableExactAmount' => false,
            'disableCash' => true,
            'disableWallet' => false,
            'sourceCode' => $this->sourceCode,
            'merchantTrns' => "Entity {$payment->entity_id} - Payment {$payment->id}",
            'tags' => [
                "payment_id:{$payment->id}",
                "entity_id:{$payment->entity_id}",
            ],
        ];

        try {
            $response = Http::withBasicAuth($this->merchantId, $this->apiKey)
                ->post("{$this->baseUrl}/checkout/v2/orders", $payload);

            if (!$response->successful()) {
                throw new \Exception("Viva API error: " . $response->body());
            }

            $data = $response->json();

            $payment->update([
                'gateway_payment_id' => $data['orderCode'],
                'status' => 'processing',
            ]);

            $this->logRequest($payment, 'order.created', $payload, $data, 'success', null, $startTime);

            $checkoutUrl = $this->baseUrl === 'https://api.vivapayments.com'
                ? "https://www.vivapayments.com/web/checkout?ref={$data['orderCode']}"
                : "https://demo.vivapayments.com/web/checkout?ref={$data['orderCode']}";

            return [
                'orderCode' => $data['orderCode'],
                'checkoutUrl' => $checkoutUrl,
            ];
        } catch (\Exception $e) {
            $this->logRequest($payment, 'order.failed', $payload, null, 'error', $e->getMessage(), $startTime);
            throw $e;
        }
    }

    public function verifyTransaction(string $transactionId): array
    {
        $response = Http::withBasicAuth($this->merchantId, $this->apiKey)
            ->get("{$this->baseUrl}/api/transactions/{$transactionId}");

        if (!$response->successful()) {
            throw new \Exception("Failed to verify transaction: " . $response->body());
        }

        return $response->json();
    }

    public function handleWebhook(array $data): array
    {
        $eventType = $data['EventTypeId'] ?? null;
        $transactionId = $data['TransactionId'] ?? null;

        if (!$transactionId) {
            return ['status' => 'error', 'message' => 'No transaction ID'];
        }

        $transactionData = $this->verifyTransaction($transactionId);

        $orderCode = $transactionData['OrderCode'] ?? null;
        if (!$orderCode) {
            return ['status' => 'error', 'message' => 'No order code'];
        }

        $payment = Payment::where('gateway_payment_id', $orderCode)->first();

        if (!$payment) {
            return ['status' => 'error', 'message' => 'Payment not found'];
        }

        $statusId = $transactionData['StatusId'] ?? null;

        if ($eventType == 1796 && $statusId === 'F') {
            // Successful payment
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'metadata' => array_merge($payment->metadata ?? [], [
                    'viva_transaction_id' => $transactionId,
                ]),
            ]);
        } elseif ($statusId === 'E') {
            // Error
            $payment->update([
                'status' => 'failed',
                'failure_reason' => 'Payment error (Viva StatusId: E)',
            ]);
        } elseif ($statusId === 'C') {
            // Cancelled
            $payment->update([
                'status' => 'failed',
                'failure_reason' => 'Payment cancelled by user',
            ]);
        }

        PaymentGatewayLog::create([
            'payment_id' => $payment->id,
            'gateway' => 'viva',
            'event_type' => "event_{$eventType}",
            'webhook_payload' => $data,
            'response_payload' => $transactionData,
            'status' => 'success',
            'created_at' => now(),
        ]);

        return ['status' => 'success', 'event' => $eventType];
    }

    public function cancelTransaction(Payment $payment, ?string $reason = null): bool
    {
        $startTime = microtime(true);

        try {
            $transactionId = $payment->metadata['viva_transaction_id'] ?? null;

            if (!$transactionId) {
                throw new \Exception('No transaction ID found');
            }

            $payload = [
                'Amount' => (int) round($payment->amount * 100),
                'ActionUser' => auth()->user()?->email ?? 'system',
            ];

            $response = Http::withBasicAuth($this->merchantId, $this->apiKey)
                ->delete("{$this->baseUrl}/api/transactions/{$transactionId}", $payload);

            if (!$response->successful()) {
                throw new \Exception("Viva API error: " . $response->body());
            }

            $payment->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);

            $this->logRequest($payment, 'transaction.cancelled', $payload, $response->json(), 'success', null, $startTime);

            return true;
        } catch (\Exception $e) {
            $this->logRequest($payment, 'transaction.cancel_failed', [], null, 'error', $e->getMessage(), $startTime);
            throw $e;
        }
    }

    protected function logRequest(Payment $payment, string $eventType, array $request, ?array $response, string $status, ?string $error, float $startTime): void
    {
        $duration = (microtime(true) - $startTime) * 1000;

        PaymentGatewayLog::create([
            'payment_id' => $payment->id,
            'gateway' => 'viva',
            'event_type' => $eventType,
            'request_payload' => $request,
            'response_payload' => $response,
            'status' => $status,
            'error_message' => $error,
            'duration_ms' => (int) $duration,
            'idempotency_key' => Str::uuid()->toString(),
            'created_at' => now(),
        ]);
    }
}
