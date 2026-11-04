<?php

namespace App\Services\Payment;

use App\Models\Payment;
use App\Models\PaymentGatewayLog;
use Stripe\StripeClient;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Illuminate\Support\Str;

class StripeService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createCheckoutSession(Payment $payment, string $successUrl, string $cancelUrl): Session
    {
        $startTime = microtime(true);

        try {
            $session = $this->stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($payment->currency),
                        'unit_amount' => (int) round($payment->amount * 100), // Convert to cents
                        'product_data' => [
                            'name' => $payment->project?->title ?? 'Payment',
                            'description' => "Payment #{$payment->id}",
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_reference_id' => $payment->id,
                'customer_email' => $payment->client?->email,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'entity_id' => $payment->entity_id,
                ],
            ]);

            $payment->update([
                'gateway_payment_id' => $session->id,
                'status' => 'processing',
            ]);

            $this->logRequest($payment, 'checkout.session.created', [
                'session_id' => $session->id,
            ], $session->toArray(), 'success', null, $startTime);

            return $session;
        } catch (\Exception $e) {
            $this->logRequest($payment, 'checkout.session.failed', [], null, 'error', $e->getMessage(), $startTime);
            throw $e;
        }
    }

    public function handleWebhook(string $payload, string $signature): array
    {
        $event = Webhook::constructEvent(
            $payload,
            $signature,
            config('services.stripe.webhook_secret')
        );

        $payment = null;
        if (isset($event->data->object->metadata->payment_id)) {
            $payment = Payment::find($event->data->object->metadata->payment_id);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                if ($payment) {
                    $payment->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'gateway_customer_id' => $event->data->object->customer ?? null,
                    ]);
                }
                break;

            case 'payment_intent.payment_failed':
                if ($payment) {
                    $payment->update([
                        'status' => 'failed',
                        'failure_reason' => $event->data->object->last_payment_error->message ?? 'Unknown error',
                    ]);
                }
                break;
        }

        if ($payment) {
            PaymentGatewayLog::create([
                'payment_id' => $payment->id,
                'gateway' => 'stripe',
                'event_type' => $event->type,
                'webhook_payload' => $event->data->object->toArray(),
                'status' => 'success',
                'created_at' => now(),
            ]);
        }

        return ['status' => 'success', 'event' => $event->type];
    }

    public function refund(Payment $payment, ?string $reason = null): bool
    {
        $startTime = microtime(true);

        try {
            $refund = $this->stripe->refunds->create([
                'payment_intent' => $payment->gateway_payment_id,
                'reason' => $reason ?? 'requested_by_customer',
            ]);

            $payment->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);

            $this->logRequest($payment, 'refund.created', ['reason' => $reason], $refund->toArray(), 'success', null, $startTime);

            return true;
        } catch (\Exception $e) {
            $this->logRequest($payment, 'refund.failed', ['reason' => $reason], null, 'error', $e->getMessage(), $startTime);
            throw $e;
        }
    }

    protected function logRequest(Payment $payment, string $eventType, array $request, ?array $response, string $status, ?string $error, float $startTime): void
    {
        $duration = (microtime(true) - $startTime) * 1000;

        PaymentGatewayLog::create([
            'payment_id' => $payment->id,
            'gateway' => 'stripe',
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
