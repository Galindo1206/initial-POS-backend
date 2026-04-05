<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CashRegisterSession;

class BlockWaiterIfRegisterOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Solo nos importa bloquear waiter
        if ($user->role === 'waiter') {

            $isRegisterOpen = CashRegisterSession::open()->exists();

            if ($isRegisterOpen) {
                return response()->json([
                    'message' => 'Cash register is open. Waiter cannot process payments.'
                ], 403);
            }
        }

        return $next($request);
    }
}
