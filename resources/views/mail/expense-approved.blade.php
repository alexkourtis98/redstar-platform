@extends('mail.layout')

@section('content')
<h2>Expense Approved</h2>

<p>Hello {{ $expense->user->name }},</p>

<p>Good news! Your expense has been approved.</p>

<div class="success-box">
    <strong>Title:</strong> {{ $expense->title }}<br>
    <strong>Amount:</strong> {{ number_format($expense->amount, 2) }} {{ $expense->currency }}<br>
    <strong>Approved by:</strong> {{ $expense->approvedBy->name ?? 'Administrator' }}<br>
    <strong>Approved on:</strong> {{ $expense->approved_at->format('F d, Y') }}
</div>

@if($expense->notes)
<p><strong>Notes:</strong><br>{{ $expense->notes }}</p>
@endif

<p>The expense will be processed and reimbursed according to the company's payment schedule.</p>

<div style="text-align: center;">
    <a href="{{ route('filament.admin.resources.expenses.view', $expense) }}" class="button">View Expense</a>
</div>

<p>Best regards,<br>{{ $expense->entity->name }}</p>
@endsection
