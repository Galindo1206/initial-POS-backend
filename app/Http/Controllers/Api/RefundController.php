<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Refund;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    // POST /api/orders/{order}/refund
    public function refund(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($request, $order, $data): JsonResponse {
            // lock para evitar refunds concurrentes
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'paid') {
                return response()->json([
                    'message' => 'Only paid orders can be refunded.',
                ], 422);
            }

            // cuánto queda por devolver
            $alreadyRefunded = (float) $order->refunds()->sum('amount');
            $refundable = max(0, (float)$order->total - $alreadyRefunded);

            if ($refundable <= 0) {
                return response()->json([
                    'message' => 'Order has no refundable amount left.',
                    'refundable' => 0,
                ], 422);
            }

            // amount (si no mandan, refund total restante)
            $amount = isset($data['amount']) ? (float)$data['amount'] : (float)$refundable;

            if ($amount > $refundable) {
                return response()->json([
                    'message' => 'Refund amount exceeds refundable amount.',
                    'refundable' => (float)$refundable,
                ], 422);
            }

            $refund = Refund::create([
                'order_id'     => $order->id,
                'amount'       => $amount,
                'reason'       => $data['reason'],
                'refunded_by'  => $request->user()->id,
                'refunded_at'  => now(),
            ]);

            // Respuesta útil para UI
            $freshOrder = $order->fresh()->load(['table:id,name', 'waiter:id,name', 'paidBy:id,name', 'refunds.refundedBy:id,name']);

            $newRefundedTotal = (float) $freshOrder->refunds->sum('amount');
            $newRefundable = max(0, (float)$freshOrder->total - $newRefundedTotal);

            return response()->json([
                'message' => 'Refund created',
                'refund' => $refund,
                'order' => $freshOrder,
                'refunded_total' => (float)$newRefundedTotal,
                'refundable' => (float)$newRefundable,
            ]);
        });
    }

    // GET /api/orders/{order}/refunds
    public function list(Order $order): JsonResponse
    {
        $order->load(['refunds.refundedBy:id,name']);

        return response()->json([
            'order_id' => $order->id,
            'refunds' => $order->refunds->map(function ($r) {
                return [
                    'id' => $r->id,
                    'amount' => (float) $r->amount,
                    'reason' => $r->reason,
                    'refunded_at' => optional($r->refunded_at)->toDateTimeString(),
                    'refunded_by' => $r->refunded_by,
                    'refunded_by_name' => optional($r->refundedBy)->name,
                ];
            }),
            'refunded_total' => (float) $order->refunds->sum('amount'),
        ]);
    }
}
