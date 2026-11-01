<?php

namespace App\Observers;

use App\Events\PaymentSucceeded;
use App\Models\Payment;

class PaymentObserver
{
    public function updated(Payment $payment): void
    {
        if ($payment->isDirty('status') && $payment->status === 'paid') {
            event(new PaymentSucceeded($payment));
        }
    }
}
