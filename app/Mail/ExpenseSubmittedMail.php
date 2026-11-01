<?php

namespace App\Mail;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpenseSubmittedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Expense $expense
    ) {
        $this->expense->load(['entity', 'user', 'category']);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Expense Submitted for Approval - {$this->expense->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.expense-submitted',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
