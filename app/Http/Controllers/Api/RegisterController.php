<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\CashRegisterSession;

class RegisterController extends Controller
{
    // ============================================================
    // GET /api/register/status
    // Devuelve si hay caja abierta
    // ============================================================
    public function status(): JsonResponse
    {
        $open = CashRegisterSession::open()->exists();

        return response()->json([
            'is_open' => $open,
        ]);
    }

    // ============================================================
    // POST /api/register/open
    // Solo cashier, admin, manager
    // ============================================================
    public function open(Request $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {

            // Verificar que no haya otra caja abierta
            if (CashRegisterSession::open()->exists()) {
                return response()->json([
                    'message' => 'Cash register already open'
                ], 422);
            }

            $session = CashRegisterSession::create([
                'opened_by' => $request->user()->id,
                'opened_at' => now(),
                'is_open'   => true,
            ]);

            return response()->json([
                'message' => 'Cash register opened',
                'session' => $session,
            ], 201);
        });
    }

    // ============================================================
    // POST /api/register/close
    // ============================================================
    public function close(Request $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {

            $session = CashRegisterSession::open()->first();

            if (!$session) {
                return response()->json([
                    'message' => 'No open cash register'
                ], 422);
            }

            $session->update([
                'closed_by' => $request->user()->id,
                'closed_at' => now(),
                'is_open'   => false,
            ]);

            return response()->json([
                'message' => 'Cash register closed',
            ]);
        });
    }
}
