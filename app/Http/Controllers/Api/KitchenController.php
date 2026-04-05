<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KitchenController extends Controller
{
    // GET /api/kitchen/orders?status=queued|preparing|ready|open
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:queued,preparing,ready,open'],
        ]);

        $status = $data['status'] ?? 'open';

        $query = Order::query()
            ->with([
                'table:id,name',
                'items' => function ($q) use ($status) {
                    $q->with('product:id,name')
                      ->when($status === 'open', function ($qq) {
                          $qq->whereIn('kitchen_status', ['queued', 'preparing']);
                      }, function ($qq) use ($status) {
                          $qq->where('kitchen_status', $status);
                      });
                },
            ])
            ->where('status', 'open'); // cocina solo trabaja órdenes abiertas normalmente

        // Solo trae órdenes que tengan items relevantes
        if ($status === 'open') {
            $query->whereHas('items', fn($q) => $q->whereIn('kitchen_status', ['queued','preparing']));
        } else {
            $query->whereHas('items', fn($q) => $q->where('kitchen_status', $status));
        }

        $orders = $query->orderByDesc('id')->get([
            'id',
            'restaurant_table_id',
            'status',
            'created_at',
        ]);

        return response()->json($orders);
    }

    // POST /api/orders/{order}/send
    // Envía a cocina todos los items draft => queued
public function sendOrder(Request $request, Order $order)
{
    $draftItems = $order->items()
        ->with('product:id,send_to_kitchen')
        ->where('kitchen_status', 'draft')
        ->get();

    if ($draftItems->isEmpty()) {
        return response()->json([
            'message' => 'No draft items to send'
        ], 422);
    }

    $sentCount = 0;

    foreach ($draftItems as $item) {
        if ($item->product && $item->product->send_to_kitchen) {
            $item->update([
                'kitchen_status' => 'queued',
                'sent_to_kitchen_at' => now(),
            ]);
            $sentCount++;
        }
    }

    return response()->json([
        'message' => $sentCount > 0
            ? 'Order items sent to kitchen'
            : 'No items in this order need kitchen',
        'sent_items' => $sentCount,
        'order_id' => $order->id,
    ], 200);
}

    // PATCH /api/kitchen/items/{item}
    // Cambia estado del item: queued|preparing|ready
public function updateItem(Request $request, $item)
{
    $orderItem = OrderItem::findOrFail($item);

    $orderItem->kitchen_status = 'ready';
    $orderItem->status = 'served'; // entregado
    $orderItem->ready_at = now();
    $orderItem->prepared_by = $request->user()->id;
    $orderItem->save();

    return response()->json([
        'message' => 'Item marked as ready',
        'item' => $orderItem,
    ], 200);
}

    // POST /api/kitchen/orders/{order}/ready
    // Marca TODOS los items queued/preparing como ready
    public function markReady(Request $request, Order $order): JsonResponse
    {
        return DB::transaction(function () use ($request, $order): JsonResponse {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $count = OrderItem::where('order_id', $order->id)
                ->whereIn('kitchen_status', ['queued', 'preparing'])
                ->update([
                    'kitchen_status' => 'ready',
                    'ready_at' => now(),
                    'prepared_by' => $request->user()->id,
                ]);

            return response()->json([
                'message' => 'Order marked ready',
                'updated_items' => (int) $count,
            ]);
        });
    }
 public function tickets(Request $request)
    {
        $status = $request->get('status', 'queued');

        $orders = Order::with([
                'table:id,name',
                'items' => function ($q) use ($status) {
                    $q->where('kitchen_status', $status)
                      ->with('product:id,name');
                }
            ])
            ->whereHas('items', function ($q) use ($status) {
                $q->where('kitchen_status', $status);
            })
            ->where('status', 'open')
            ->orderByDesc('id')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_id' => $order->id,
                    'table_name' => $order->table?->name ?? 'Mesa',
                    'items_count' => $order->items->count(),
                    'items' => $order->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'qty' => $item->qty,
                            'notes' => $item->notes,
                            'kitchen_status' => $item->kitchen_status,
                            'sent_to_kitchen_at' => $item->sent_to_kitchen_at,
                            'product' => [
                                'id' => $item->product?->id,
                                'name' => $item->product?->name ?? 'Item',
                            ],
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json($orders, 200);
    }
}
