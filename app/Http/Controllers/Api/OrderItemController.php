<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrderItemController extends Controller
{
    // GET /api/orders/{order}/items
    public function index(Order $order): JsonResponse
    {
        $order->load('items.product');

        return response()->json([
            'order' => $order,
        ], 200);
    }

    // POST /api/orders/{order}/items
public function store(Request $request, Order $order)
{
    if ($order->status !== 'open') {
        return response()->json([
            'message' => 'Order is not open'
        ], 422);
    }

    $data = $request->validate([
        'product_id' => ['required', 'exists:products,id'],
        'qty'        => ['required', 'integer', 'min:1'],
        'notes'      => ['nullable', 'string'],
    ]);

    $product = Product::findOrFail($data['product_id']);

    $orderItem = $order->items()->create([
        'product_id' => $product->id,
        'qty' => $data['qty'],
        'unit_price' => $product->price,
        'notes' => $data['notes'] ?? null,
        'status' => 'pending',
        'kitchen_status' => 'draft',
    ]);

    $order->recalcTotals();
    $order->load('items.product');

    return response()->json([
        'message' => 'Item added to order',
        'item'    => $orderItem,
        'order'   => $order,
    ], 201);
}

    // PUT /api/order-items/{item}
    public function update(Request $request, OrderItem $item): JsonResponse
    {
        $order = $item->order;

        if ($order->status !== 'open') {
            return response()->json(['message' => 'Order is not open'], 422);
        }

        $data = $request->validate([
            'qty' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:pending,sent,served,void'],
        ]);

        return DB::transaction(function () use ($data, $item, $order) {

            $item->fill($data)->save();

            $order->recalcTotals();

            $item->load('product');
            $freshOrder = $order->fresh()->load('items.product');

            return response()->json([
                'message' => 'Item updated',
                'item' => $item,
                'order' => $freshOrder,
            ], 200);
        });
    }

    // DELETE /api/order-items/{item}
    public function destroy(OrderItem $item): JsonResponse
    {
        $order = $item->order;

        if ($order->status !== 'open') {
            return response()->json(['message' => 'Order is not open'], 422);
        }

        return DB::transaction(function () use ($item, $order) {

            $item->delete();

            $order->recalcTotals();

            $freshOrder = $order->fresh()->load('items.product');

            return response()->json([
                'message' => 'Item deleted',
                'order' => $freshOrder,
            ], 200);
        });
    }
}
