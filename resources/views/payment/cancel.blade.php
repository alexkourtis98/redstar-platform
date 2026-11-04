@extends('layouts.guest')

@section('content')
<div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
        {{-- Cancel Icon --}}
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-amber-100">
            <svg class="h-10 w-10 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>

        {{-- Cancel Message --}}
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Payment Cancelled</h1>
            <p class="mt-2 text-gray-600">
                Your payment was not processed. You can try again at any time.
            </p>
        </div>

        {{-- Action Buttons --}}
        <div class="space-y-3">
            @if(isset($payment))
            <a href="{{ route('payment.checkout', $payment->payment_link_token) }}"
               class="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white shadow-sm transition-colors"
               style="background-color: var(--semantic-action-primary, #2563eb);"
               onmouseover="this.style.backgroundColor='var(--semantic-action-primary-hover, #1d4ed8)'"
               onmouseout="this.style.backgroundColor='var(--semantic-action-primary, #2563eb)'">
                Try Again
            </a>
            @endif

            <p class="text-sm text-gray-500">
                Need help? Contact support at <a href="mailto:support@{{ parse_url(config('app.url'), PHP_URL_HOST) }}" class="text-blue-600 hover:underline">support@{{ parse_url(config('app.url'), PHP_URL_HOST) }}</a>
            </p>
        </div>
    </div>
</div>
@endsection
