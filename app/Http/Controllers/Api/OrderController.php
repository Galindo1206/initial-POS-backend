<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Payment;

class OrderController extends Controller
{
    // POST /api/orders/{order}/pay
    public function pay(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'tip'         => ['nullable', 'numeric', 'min:0'],
            'method'      => ['required', 'in:cash,card,mixed'],
            // Para cash/mixed (opcional en card)
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($data, $order, $request): JsonResponse {

            // Lock para evitar doble pago concurrente
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->status !== 'open') {
                return response()->json(['message' => 'Order is not open'], 422);
            }

            // 1) Set tip primero
            $order->tip = (float)($data['tip'] ?? 0);

            // 2) Recalcular subtotal/total en backend (fuente de verdad)
            $order->recalcTotals(); // usa $this->tip para total

            // (Opcional) Si quieres impedir pagar una orden vacía:
            // if ($order->subtotal <= 0) {
            //     return response()->json(['message' => 'Order has no items'], 422);
            // }

            $method = $data['method'];

            // 3) Validación cash/mixed: amount_paid debe alcanzar total
            $amountPaid = isset($data['amount_paid']) ? (float)$data['amount_paid'] : null;

            if (in_array($method, ['cash', 'mixed'], true)) {
                if ($amountPaid === null) {
                    return response()->json(['message' => 'amount_paid is required for cash/mixed'], 422);
                }
                if ($amountPaid < (float)$order->total) {
                    return response()->json([
                        'message' => 'amount_paid is insufficient',
                        'total'   => (float)$order->total,
                    ], 422);
                }
            }

            // 4) Marcar orden como pagada
            $order->status = 'paid';
            $order->paid_at = now();
            $order->paid_by = $request->user()->id;
            $order->save();

            // 5) Registrar pago(s)
            // MVP: 1 payment
            // Para "mixed" real (2 payments) lo hacemos luego si lo quieres.
            $paymentAmount = ($method === 'card') ? (float)$order->total : (float)($amountPaid ?? $order->total);

            $payment = Payment::create([
                'order_id' => $order->id,
                'method'   => $method,
                'amount'   => $paymentAmount,
            ]);

            $change = null;
            if ($method === 'cash') {
                $change = (float)$amountPaid - (float)$order->total;
            }

            // 6) Liberar mesa
            $order->table()->update(['status' => 'free']);

            // 7) Respuesta consistente
            $freshOrder = $order->fresh()->load('items.product', 'payments');

            return response()->json([
                'message' => 'Order paid',
                'order'   => $freshOrder,
                'change'  => $change, // null si no aplica
                'payment' => $payment,
            ], 200);
        });
    }

    // GET /api/orders/{order}
public function show(Request $request, Order $order): JsonResponse
{
    // Permisos: caja/manager/admin pueden ver cualquiera.
    // waiter/kitchen solo deberían ver lo necesario (luego lo cerramos).
    // Por ahora: permitir si está autenticado.
    // (En el siguiente paso ponemos reglas por role bien cerradas)

    $order->load([
        'items.product',
        // Si tu relación se llama distinto, lo ajustamos luego:
        'table:id,name',
        'payments',
        'paidBy:id,name',
    ]);

    return response()->json($order);
}

}
