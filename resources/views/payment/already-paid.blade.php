@extends('layouts.guest')

@section('content')
<div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8 text-center">
        {{-- Info Icon --}}
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100">
            <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>

        {{-- Message --}}
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Already Paid</h1>
            <p class="mt-2 text-gray-600">
                This payment link has already been used. If you believe this is an error, please contact support.
            </p>
        </div>

        {{-- Payment Details --}}
        @if(isset($payment))
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 text-left">
            <dl class="space-y-2">
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600">Amount</dt>
                    <dd class="text-sm font-bold text-gray-900">{{ $payment->formatted_amount }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600">Status</dt>
                    <dd class="text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Paid
                        </span>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm font-medium text-gray-600">Paid On</dt>
                    <dd class="text-sm text-gray-900">{{ $payment->paid_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="space-y-3">
            <a href="{{ route('portal.dashboard') }}"
               class="w-full inline-flex justify-center items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                Go to Dashboard
            </a>

            <p class="text-sm text-gray-500">
                Questions? Contact <a href="mailto:support@{{ parse_url(config('app.url'), PHP_URL_HOST) }}" class="text-blue-600 hover:underline">support@{{ parse_url(config('app.url'), PHP_URL_HOST) }}</a>
            </p>
        </div>
    </div>
</div>
@endsection
