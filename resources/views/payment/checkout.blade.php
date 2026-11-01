@extends('layouts.guest')

@section('content')
<div class="flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        {{-- Header --}}
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900">Complete Payment</h1>
            <p class="mt-2 text-sm text-gray-600">
                Secure payment powered by Stripe and Viva Wallet
            </p>
        </div>

        {{-- Payment Amount Card --}}
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <span class="text-sm font-medium text-gray-600">Amount Due</span>
                <span class="text-2xl font-bold" style="color: var(--semantic-action-primary, #2563eb);">
                    {{ $payment->formatted_amount }}
                </span>
            </div>

            @if($payment->project)
            <div class="pt-4 border-t border-gray-200">
                <p class="text-sm text-gray-600">
                    <span class="font-medium">Project:</span> {{ $payment->project->title }}
                </p>
                @if($payment->client)
                <p class="text-sm text-gray-600 mt-1">
                    <span class="font-medium">Client:</span> {{ $payment->client->name }}
                </p>
                @endif
            </div>
            @endif
        </div>

        {{-- Error Message --}}
        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">
                        Please correct the following errors:
                    </h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        {{-- Payment Form --}}
        <form method="POST" action="{{ route('payment.process', $payment->payment_link_token) }}" class="bg-white rounded-lg shadow-md p-6 border border-gray-200 space-y-6">
            @csrf

            {{-- Customer Information --}}
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Your Information</h3>

                <div class="space-y-4">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email', $payment->client->email ?? '') }}"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            placeholder="you@example.com"
                        >
                        @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            value="{{ old('name', $payment->client->name ?? '') }}"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            placeholder="John Doe"
                        >
                        @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number (Optional)</label>
                        <input
                            type="tel"
                            id="phone"
                            name="phone"
                            value="{{ old('phone', $payment->client->phone ?? '') }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                            placeholder="+30 123 456 7890"
                        >
                    </div>
                </div>
            </div>

            {{-- Payment Gateway Selection --}}
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Select Payment Method</h3>

                <div class="space-y-3">
                    {{-- Stripe Option --}}
                    <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all hover:bg-gray-50"
                           :class="{'border-blue-500 bg-blue-50': gateway === 'stripe', 'border-gray-300': gateway !== 'stripe'}"
                           x-data="{ gateway: '{{ old('gateway', 'stripe') }}' }"
                           @click="gateway = 'stripe'">
                        <input
                            type="radio"
                            name="gateway"
                            value="stripe"
                            x-model="gateway"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                            checked
                        >
                        <div class="ml-3 flex-1">
                            <div class="flex items-center justify-between">
                                <span class="block text-sm font-medium text-gray-900">Credit / Debit Card</span>
                                <svg class="h-6 w-auto" viewBox="0 0 84 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M38.9 8.8H32.3L36.6 15.2L32.3 21.6H38.9L43.2 15.2L38.9 8.8Z" fill="#6772E5"/>
                                    <path d="M27.7 8.8H21.1L25.4 15.2L21.1 21.6H27.7L32 15.2L27.7 8.8Z" fill="#6772E5"/>
                                </svg>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Powered by Stripe - Visa, Mastercard, Amex</p>
                        </div>
                    </label>

                    {{-- Viva Wallet Option --}}
                    <label class="relative flex items-center p-4 border-2 rounded-lg cursor-pointer transition-all hover:bg-gray-50"
                           :class="{'border-blue-500 bg-blue-50': gateway === 'viva', 'border-gray-300': gateway !== 'viva'}"
                           x-data="{ gateway: '{{ old('gateway', 'stripe') }}' }"
                           @click="gateway = 'viva'">
                        <input
                            type="radio"
                            name="gateway"
                            value="viva"
                            x-model="gateway"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                        >
                        <div class="ml-3 flex-1">
                            <div class="flex items-center justify-between">
                                <span class="block text-sm font-medium text-gray-900">Viva Wallet</span>
                                <span class="text-sm font-semibold text-blue-600">VIVA</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">European payment gateway - Cards & Digital Wallets</p>
                        </div>
                    </label>
                </div>

                @error('gateway')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit Button --}}
            <div class="pt-4">
                <button
                    type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white transition-colors"
                    style="background-color: var(--semantic-action-primary, #2563eb);"
                    onmouseover="this.style.backgroundColor='var(--semantic-action-primary-hover, #1d4ed8)'"
                    onmouseout="this.style.backgroundColor='var(--semantic-action-primary, #2563eb)'"
                >
                    Continue to Secure Payment
                </button>

                <p class="mt-3 text-xs text-center text-gray-500">
                    Your payment is secured with SSL encryption
                </p>
            </div>
        </form>

        {{-- Security Notice --}}
        <div class="text-center">
            <p class="text-xs text-gray-500">
                By proceeding, you agree to our terms of service and privacy policy
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
@endpush
@endsection
