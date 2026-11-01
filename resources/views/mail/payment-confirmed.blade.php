@extends('mail.layout')

@section('content')
<h2>Payment Confirmed</h2>

<p>Hello {{ $payment->client->name ?? 'Customer' }},</p>

<p>We've received your payment. Thank you!</p>

<div class="success-box">
    <strong>Amount Paid:</strong> {{ number_format($payment->amount, 2) }} {{ $payment->currency }}<br>
    <strong>Payment Method:</strong> {{ ucfirst($payment->gateway) }}<br>
    <strong>Transaction ID:</strong> {{ $payment->gateway_payment_id }}<br>
    <strong>Date:</strong> {{ $payment->paid_at->format('F d, Y \a\t H:i') }}
</div>

@if($payment->project)
<p><strong>Project:</strong> {{ $payment->project->title }}</p>
@endif

@if($payment->invoice)
<p>Your invoice (#{{ $payment->invoice->invoice_number }}) will be sent to you shortly via a separate email.</p>
@else
<p>Your invoice will be generated and sent to you within 24 hours.</p>
@endif

<div style="text-align: center;">
    <a href="{{ route('portal.dashboard') }}" class="button">View Dashboard</a>
</div>

<p>If you have any questions about this payment, please don't hesitate to contact us.</p>

<p>Best regards,<br>{{ $payment->entity->name }}</p>
@endsection
