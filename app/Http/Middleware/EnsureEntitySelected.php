<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEntitySelected
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Super admin can access without entity selection
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if entity is already selected
        if (session()->has('current_entity_id')) {
            $entityId = session('current_entity_id');

            // Verify user still has access to this entity
            if ($user->entities()->where('entities.id', $entityId)->exists()) {
                return $next($request);
            }
        }

        // Try to use last entity
        if ($user->last_entity_id && $user->entities()->where('entities.id', $user->last_entity_id)->exists()) {
            session(['current_entity_id' => $user->last_entity_id]);
            return $next($request);
        }

        // Try to use default entity
        $defaultEntity = $user->entities()->wherePivot('is_default', true)->first();
        if ($defaultEntity) {
            session(['current_entity_id' => $defaultEntity->id]);
            $user->update(['last_entity_id' => $defaultEntity->id]);
            return $next($request);
        }

        // Try first entity
        $firstEntity = $user->entities()->first();
        if ($firstEntity) {
            session(['current_entity_id' => $firstEntity->id]);
            $user->update(['last_entity_id' => $firstEntity->id]);
            return $next($request);
        }

        // No entities assigned
        abort(403, 'No entity assigned to your account.');
    }
}
