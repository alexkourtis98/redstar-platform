@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Invoice {{ $invoice->invoice_number }}</h1>
                <p class="mt-2 text-gray-600">Issued {{ $invoice->issue_date->format('F d, Y') }}</p>
            </div>
            <a href="{{ route('portal.invoices.download', $invoice) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white"
               style="background-color: var(--semantic-action-primary, #2563eb);">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Download PDF
            </a>
        </div>

        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-5 border-b border-gray-200 sm:px-6">
                <div class="flex justify-between">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900">{{ $entity->name }}</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            @if($entity->email){{ $entity->email }}<br>@endif
                            @if($entity->phone){{ $entity->phone }}@endif
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-600">Bill To</p>
                        <p class="mt-1 text-sm text-gray-900">{{ $client->name }}</p>
                        @if($client->email)<p class="text-sm text-gray-500">{{ $client->email }}</p>@endif
                    </div>
                </div>
            </div>

            @if($invoice->mark)
            <div class="px-6 py-4 bg-amber-50 border-b border-amber-200">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-amber-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-sm font-medium text-amber-800">AADE MARK: {{ $invoice->mark }}</span>
                </div>
            </div>
            @endif

            <div class="px-6 py-5">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Invoice Number</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->invoice_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Issue Date</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $invoice->issue_date->format('F d, Y') }}</dd>
                    </div>
                    @if($project)
                    <div class="sm:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Project</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $project->title }}</dd>
                    </div>
                    @endif
                </dl>

                <div class="mt-8">
                    <h3 class="text-lg font-medium text-gray-900">Amount Details</h3>
                    <dl class="mt-4 space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Subtotal</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ number_format($invoice->subtotal, 2) }} {{ $invoice->currency }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">VAT (24%)</dt>
                            <dd class="text-sm font-medium text-gray-900">{{ number_format($invoice->vat_amount, 2) }} {{ $invoice->currency }}</dd>
                        </div>
                        <div class="flex justify-between pt-4 border-t border-gray-200">
                            <dt class="text-base font-semibold text-gray-900">Total</dt>
                            <dd class="text-base font-bold" style="color: var(--semantic-action-primary, #2563eb);">
                                {{ number_format($invoice->total, 2) }} {{ $invoice->currency }}
                            </dd>
                        </div>
                    </dl>
                </div>

                @if($payment)
                <div class="mt-8">
                    <h3 class="text-lg font-medium text-gray-900">Payment Information</h3>
                    <dl class="mt-4 space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Payment Method</dt>
                            <dd class="text-sm text-gray-900">{{ ucfirst($payment->gateway) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Transaction ID</dt>
                            <dd class="text-sm font-mono text-gray-900">{{ $payment->gateway_payment_id }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-sm text-gray-600">Paid On</dt>
                            <dd class="text-sm text-gray-900">{{ $payment->paid_at->format('F d, Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
