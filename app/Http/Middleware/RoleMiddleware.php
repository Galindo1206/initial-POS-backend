<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Uso:
     *  ->middleware('role:admin')
     *  ->middleware('role:admin,manager,waiter')
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // roles llega como ["admin,manager"] cuando se pasa con comas
        // así que lo normalizamos:
        $allowed = [];
        foreach ($roles as $chunk) {
            foreach (explode(',', (string)$chunk) as $r) {
                $r = trim($r);
                if ($r !== '') $allowed[] = $r;
            }
        }

        $allowed = array_values(array_unique($allowed));
        $role = (string) ($user->role ?? '');

        // ✅ Admin override (siempre pasa)
        if ($role === 'admin') {
            return $next($request);
        }

        if (!in_array($role, $allowed, true)) {
            return response()->json([
                'message' => 'Forbidden',
                'required_roles' => $allowed,
                'your_role' => $role,
            ], 403);
        }

        return $next($request);
    }
}
