<?php

namespace App\Observers;

use App\Mail\InvoiceDeliveryMail;
use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;

class InvoiceObserver
{
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        // Send invoice email when AADE MARK is received and PDF is ready
        if ($invoice->wasChanged('mark') && $invoice->mark && $invoice->pdf_path) {
            Mail::to($invoice->client->email)
                ->send(new InvoiceDeliveryMail($invoice));
        }
    }

    /**
     * Handle the Invoice "deleted" event.
     */
    public function deleted(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "restored" event.
     */
    public function restored(Invoice $invoice): void
    {
        //
    }

    /**
     * Handle the Invoice "force deleted" event.
     */
    public function forceDeleted(Invoice $invoice): void
    {
        //
    }
}
