@extends('mail.layout')

@section('content')
<h2>Expense Rejected</h2>

<p>Hello {{ $expense->user->name }},</p>

<p>Unfortunately, your expense has been rejected.</p>

<div class="warning-box">
    <strong>Title:</strong> {{ $expense->title }}<br>
    <strong>Amount:</strong> {{ number_format($expense->amount, 2) }} {{ $expense->currency }}<br>
    <strong>Rejected on:</strong> {{ now()->format('F d, Y') }}
</div>

@if($expense->rejection_reason)
<p><strong>Reason:</strong><br>{{ $expense->rejection_reason }}</p>
@endif

<p>If you believe this was rejected in error or if you have additional information to provide, please contact your manager or submit a new expense with the necessary documentation.</p>

<div style="text-align: center;">
    <a href="{{ route('filament.admin.resources.expenses.edit', $expense) }}" class="button">View Expense</a>
</div>

<p>Best regards,<br>{{ $expense->entity->name }}</p>
@endsection
