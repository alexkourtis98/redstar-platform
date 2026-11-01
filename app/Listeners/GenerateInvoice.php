<?php

namespace App\Listeners;

use App\Events\PaymentSucceeded;
use App\Models\Invoice;
use App\Services\Invoicing\AadeInvoicingService;
use App\Services\Invoicing\InvoicePdfService;
use Illuminate\Support\Facades\Log;

class GenerateInvoice
{
    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment;

        // Check if invoice already exists
        if ($payment->invoice()->exists()) {
            Log::info("Invoice already exists for payment {$payment->id}");
            return;
        }

        $entity = $payment->entity;

        // Create invoice
        $fiscalYear = now()->year;
        $sequenceNumber = Invoice::where('entity_id', $entity->id)
            ->where('fiscal_year', $fiscalYear)
            ->max('sequence_number') + 1;

        $invoiceNumber = sprintf('%s-%04d-%05d', $entity->slug, $fiscalYear, $sequenceNumber);

        $invoice = Invoice::create([
            'entity_id' => $payment->entity_id,
            'payment_id' => $payment->id,
            'client_id' => $payment->client_id,
            'project_id' => $payment->project_id,
            'invoice_number' => $invoiceNumber,
            'series_code' => $entity->slug,
            'sequence_number' => $sequenceNumber,
            'fiscal_year' => $fiscalYear,
            'issue_date' => now()->toDateString(),
            'document_type' => '2.1', // Services
            'line_items' => [[
                'description' => $payment->project?->title ?? "Payment #{$payment->id}",
                'quantity' => 1,
                'unit_price' => $payment->amount,
                'vat_rate' => 24,
                'vat_amount' => $payment->amount * 0.24,
                'total' => $payment->amount * 1.24,
            ]],
            'subtotal' => $payment->amount,
            'vat_amount' => $payment->amount * 0.24,
            'total' => $payment->amount * 1.24,
            'currency' => $payment->currency,
            'status' => 'draft',
        ]);

        // Transmit to AADE if enabled
        if ($entity->aade_enabled) {
            try {
                $aadeService = app(AadeInvoicingService::class);
                $aadeService->transmitToAade($invoice);

                Log::info("Invoice {$invoice->id} transmitted to AADE successfully");

                // Generate PDF after successful AADE transmission (includes MARK + QR)
                $pdfService = app(InvoicePdfService::class);
                $pdfPath = $pdfService->generatePdf($invoice->fresh());
                Log::info("Invoice PDF generated: {$pdfPath}");

            } catch (\Exception $e) {
                Log::error("AADE transmission failed for invoice {$invoice->id}: " . $e->getMessage());

                $invoice->update([
                    'status' => 'failed',
                    'failure_message' => $e->getMessage(),
                ]);
            }
        } else {
            $invoice->update(['status' => 'issued']);

            // Generate PDF even without AADE
            try {
                $pdfService = app(InvoicePdfService::class);
                $pdfPath = $pdfService->generatePdf($invoice->fresh());
                Log::info("Invoice PDF generated: {$pdfPath}");
            } catch (\Exception $e) {
                Log::error("PDF generation failed for invoice {$invoice->id}: " . $e->getMessage());
            }
        }
    }
}
