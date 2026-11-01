<?php

namespace App\Http\Controllers;

use App\Services\Payment\StripeService;
use App\Services\Payment\VivaWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function stripe(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $stripeService = app(StripeService::class);
            $result = $stripeService->handleWebhook($payload, $signature);

            Log::info('Stripe webhook processed', $result);

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('Stripe webhook failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function viva(Request $request)
    {
        $webhookKey = $request->header('Authorization');
        $expectedKey = 'Bearer ' . config('services.viva.webhook_key');

        if ($webhookKey !== $expectedKey) {
            Log::warning('Viva webhook unauthorized', [
                'received' => $webhookKey,
            ]);

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $vivaService = app(VivaWalletService::class);
            $result = $vivaService->handleWebhook($request->all());

            Log::info('Viva webhook processed', $result);

            return response()->json(['status' => 'success'], 200);
        } catch (\Exception $e) {
            Log::error('Viva webhook failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
