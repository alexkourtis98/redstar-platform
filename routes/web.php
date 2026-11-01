<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Payment routes
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('checkout/{token}', [PaymentController::class, 'checkout'])->name('checkout');
    Route::post('checkout/{token}', [PaymentController::class, 'processCheckout'])->name('process');
    Route::get('success/{token}', [PaymentController::class, 'success'])->name('success');
    Route::get('cancel/{token}', [PaymentController::class, 'cancel'])->name('cancel');
});

// Webhook routes (no CSRF protection)
Route::post('webhooks/stripe', [WebhookController::class, 'stripe'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('webhooks/viva', [WebhookController::class, 'viva'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Customer Portal routes (authenticated)
Route::middleware('auth')->prefix('portal')->name('portal.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\PortalController::class, 'dashboard'])->name('dashboard');
    Route::get('/invoices', [\App\Http\Controllers\PortalController::class, 'invoices'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [\App\Http\Controllers\PortalController::class, 'showInvoice'])->name('invoices.show');
    Route::get('/invoices/{invoice}/download', [\App\Http\Controllers\PortalController::class, 'downloadInvoice'])->name('invoices.download');
});
