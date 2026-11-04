<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payment\StripeService;
use App\Services\Payment\VivaWalletService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function checkout(string $token)
    {
        $payment = Payment::where('payment_link_token', $token)->firstOrFail();

        if ($payment->linkExpired()) {
            abort(410, 'Payment link has expired');
        }

        if ($payment->isPaid()) {
            return view('payment.already-paid', compact('payment'));
        }

        return view('payment.checkout', compact('payment'));
    }

    public function processCheckout(Request $request, string $token)
    {
        $request->validate([
            'gateway' => 'required|in:stripe,viva',
        ]);

        $payment = Payment::where('payment_link_token', $token)->firstOrFail();

        if ($payment->linkExpired() || $payment->isPaid()) {
            return redirect()->route('payment.checkout', $token);
        }

        $payment->update(['gateway' => $request->gateway]);

        $successUrl = route('payment.success', $token);
        $cancelUrl = route('payment.checkout', $token);

        try {
            if ($request->gateway === 'stripe') {
                $stripeService = app(StripeService::class);
                $session = $stripeService->createCheckoutSession($payment, $successUrl, $cancelUrl);
                return redirect($session->url);
            } else {
                $vivaService = app(VivaWalletService::class);
                $result = $vivaService->createPaymentOrder($payment, $successUrl, $cancelUrl);
                return redirect($result['checkoutUrl']);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Payment processing failed: ' . $e->getMessage()]);
        }
    }

    public function success(string $token)
    {
        $payment = Payment::where('payment_link_token', $token)->firstOrFail();

        if (!$payment->isPaid()) {
            return redirect()->route('payment.checkout', $token);
        }

        return view('payment.success', compact('payment'));
    }

    public function cancel(string $token)
    {
        $payment = Payment::where('payment_link_token', $token)->firstOrFail();

        return view('payment.cancel', compact('payment'));
    }
}
