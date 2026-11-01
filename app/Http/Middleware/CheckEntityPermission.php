<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEntityPermission
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Super admin bypasses all checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $entityId = session('current_entity_id');

        if (!$entityId) {
            abort(403, 'No entity selected.');
        }

        $roles = explode('|', $role);

        if (!$user->hasEntityRole($entityId, $roles)) {
            abort(403, 'Insufficient permissions for this entity.');
        }

        return $next($request);
    }
}
