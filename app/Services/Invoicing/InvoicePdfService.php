<?php

namespace App\Services\Invoicing;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    /**
     * Generate PDF from invoice and store in storage
     *
     * @param Invoice $invoice
     * @return string Path to stored PDF
     */
    public function generatePdf(Invoice $invoice): string
    {
        $invoice->load(['entity', 'client', 'project', 'payment']);

        // Generate PDF
        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'entity' => $invoice->entity,
            'client' => $invoice->client,
            'project' => $invoice->project,
            'payment' => $invoice->payment,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        // Define storage path
        $filename = sprintf(
            'invoices/%s/%s-%s.pdf',
            $invoice->entity->slug,
            $invoice->invoice_number,
            now()->format('YmdHis')
        );

        // Store PDF
        Storage::disk('local')->put($filename, $pdf->output());

        // Update invoice with PDF path
        $invoice->update(['pdf_path' => $filename]);

        return $filename;
    }

    /**
     * Get PDF stream for download
     *
     * @param Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function downloadPdf(Invoice $invoice)
    {
        if (!$invoice->pdf_path || !Storage::disk('local')->exists($invoice->pdf_path)) {
            // Regenerate if missing
            $this->generatePdf($invoice);
        }

        $filename = sprintf(
            'Invoice-%s-%s.pdf',
            $invoice->invoice_number,
            $invoice->entity->name
        );

        return Storage::disk('local')->download($invoice->pdf_path, $filename);
    }

    /**
     * Get PDF content as string (for email attachment)
     *
     * @param Invoice $invoice
     * @return string
     */
    public function getPdfContent(Invoice $invoice): string
    {
        if (!$invoice->pdf_path || !Storage::disk('local')->exists($invoice->pdf_path)) {
            $this->generatePdf($invoice);
        }

        return Storage::disk('local')->get($invoice->pdf_path);
    }
}
