<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Payment::observe(\App\Observers\PaymentObserver::class);
        \App\Models\Invoice::observe(\App\Observers\InvoiceObserver::class);

        \Illuminate\Support\Facades\Event::listen(
            \App\Events\PaymentSucceeded::class,
            \App\Listeners\GenerateInvoice::class,
        );
    }
}
