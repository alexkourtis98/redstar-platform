<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\Invoicing\InvoicePdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceDeliveryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {
        $this->invoice->load(['entity', 'client', 'project', 'payment']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice {$this->invoice->invoice_number} - {$this->invoice->entity->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.invoice-delivery',
        );
    }

    public function attachments(): array
    {
        if (!$this->invoice->pdf_path) {
            return [];
        }

        $pdfService = app(InvoicePdfService::class);
        $pdfContent = $pdfService->getPdfContent($this->invoice);

        return [
            Attachment::fromData(fn() => $pdfContent, "Invoice-{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
