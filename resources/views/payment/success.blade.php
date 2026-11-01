@extends('layouts.guest')

@section('content')
<div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
        {{-- Success Icon --}}
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
            <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>

        {{-- Success Message --}}
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Payment Successful</h1>
            <p class="mt-2 text-gray-600">
                Thank you for your payment. You will receive a confirmation email shortly.
            </p>
        </div>

        {{-- Payment Details --}}
        @if(isset($payment))
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 text-left">
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600">Amount Paid</dt>
                    <dd class="text-sm font-bold text-gray-900">{{ $payment->formatted_amount }}</dd>
                </div>
                @if($payment->project)
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600">Project</dt>
                    <dd class="text-sm text-gray-900">{{ $payment->project->title }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600">Payment Method</dt>
                    <dd class="text-sm text-gray-900 capitalize">{{ $payment->gateway }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600">Transaction ID</dt>
                    <dd class="text-sm text-gray-900 font-mono">{{ $payment->gateway_payment_id }}</dd>
                </div>
            </dl>
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="space-y-3">
            <a href="{{ route('portal.dashboard') }}"
               class="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white shadow-sm transition-colors"
               style="background-color: var(--semantic-action-primary, #2563eb);"
               onmouseover="this.style.backgroundColor='var(--semantic-action-primary-hover, #1d4ed8)'"
               onmouseout="this.style.backgroundColor='var(--semantic-action-primary, #2563eb)'">
                Go to Dashboard
            </a>

            <p class="text-sm text-gray-500">
                An invoice will be sent to your email address within 24 hours.
            </p>
        </div>
    </div>
</div>
@endsection
