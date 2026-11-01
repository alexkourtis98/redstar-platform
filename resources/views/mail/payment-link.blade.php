@extends('mail.layout')

@section('content')
<h2>Payment Request</h2>

<p>Hello {{ $payment->client->name ?? 'Customer' }},</p>

<p>We've prepared a secure payment link for you.</p>

<div class="info-box">
    <strong>Amount Due:</strong> {{ number_format($payment->amount, 2) }} {{ $payment->currency }}<br>
    @if($payment->project)
        <strong>Project:</strong> {{ $payment->project->title }}<br>
    @endif
    <strong>Type:</strong> {{ ucfirst($payment->type) }}
</div>

@if($payment->project && $payment->project->description)
<p><strong>Description:</strong><br>{{ $payment->project->description }}</p>
@endif

<div style="text-align: center; margin: 30px 0;">
    <a href="{{ $checkoutUrl }}" class="button">Pay Now</a>
</div>

<p>This payment link will expire on {{ $payment->link_expires_at->format('F d, Y \a\t H:i') }}.</p>

<p>You can choose to pay via credit card (Stripe) or Viva Wallet at checkout.</p>

<p>If you have any questions, please reply to this email.</p>

<p>Best regards,<br>{{ $payment->entity->name }}</p>
@endsection
