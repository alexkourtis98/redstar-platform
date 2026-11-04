@extends('mail.layout')

@section('content')
<h2>New Expense Submitted</h2>

<p>Hello,</p>

<p>A new expense has been submitted for your approval.</p>

<div class="info-box">
    <strong>Title:</strong> {{ $expense->title }}<br>
    <strong>Submitted by:</strong> {{ $expense->user->name }}<br>
    <strong>Amount:</strong> {{ number_format($expense->amount, 2) }} {{ $expense->currency }}<br>
    <strong>Category:</strong> {{ $expense->category->name ?? 'Uncategorized' }}<br>
    <strong>Date:</strong> {{ $expense->expense_date->format('F d, Y') }}
</div>

@if($expense->description)
<p><strong>Description:</strong><br>{{ $expense->description }}</p>
@endif

@if($expense->merchant)
<p><strong>Merchant:</strong> {{ $expense->merchant }}</p>
@endif

<div style="text-align: center;">
    <a href="{{ route('filament.admin.resources.expenses.edit', $expense) }}" class="button">Review Expense</a>
</div>

<p>Please review and approve or reject this expense at your earliest convenience.</p>

<p>Best regards,<br>{{ $expense->entity->name }}</p>
@endsection
