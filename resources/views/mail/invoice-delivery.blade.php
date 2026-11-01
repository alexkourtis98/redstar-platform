@extends('mail.layout')

@section('content')
<h2>Your Invoice is Ready</h2>

<p>Hello {{ $client->name }},</p>

<p>Thank you for your payment. Your invoice is now available.</p>

<div class="success-box">
    <strong>Invoice Number:</strong> {{ $invoice->invoice_number }}<br>
    <strong>Date:</strong> {{ $invoice->issue_date->format('F d, Y') }}<br>
    <strong>Amount:</strong> {{ number_format($invoice->total, 2) }} {{ $invoice->currency }}<br>
    @if($invoice->mark)
        <strong>AADE MARK:</strong> <code>{{ $invoice->mark }}</code>
    @endif
</p>
</div>

@if($project)
<p><strong>Project:</strong> {{ $project->title }}</p>
@endif

<p>The invoice PDF is attached to this email. You can also download it anytime from your customer portal.</p>

@if($invoice->qr_url)
<p style="text-align: center; margin: 30px 0;">
    <img src="{{ $invoice->qr_url }}" alt="Invoice QR Code" style="width: 150px; height: 150px;">
    <br>
    <small>Scan to verify with AADE</small>
</p>
@endif

<div style="text-align: center;">
    <a href="{{ route('portal.invoices.show', $invoice) }}" class="button">View in Portal</a>
</div>

<p>If you have any questions, please don't hesitate to contact us.</p>

<p>Best regards,<br>{{ $entity->name }}</p>
@endsection
