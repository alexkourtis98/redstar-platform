<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectBrand
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        $brand = match (true) {
            str_starts_with($host, 'redstar.') || str_contains($host, 'redstar') => 'redstar',
            str_starts_with($host, 'blueline.') || str_contains($host, 'blueline') => 'blueline',
            str_starts_with($host, 'greenpromo.') || str_contains($host, 'greenpromo') => 'greenpromo',
            str_starts_with($host, 'amberlane.') || str_contains($host, 'amberlane') => 'amberlane',
            default => 'redstar'
        };

        config(['app.brand' => $brand]);
        view()->share('brand', $brand);

        return $next($request);
    }
}
