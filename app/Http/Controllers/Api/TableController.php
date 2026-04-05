<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Order;

class TableController extends Controller
{
public function index(): JsonResponse
{
    $tables = RestaurantTable::with('activeOrder')
        ->orderBy('id')
        ->get()
        ->map(function ($table) {
            return [
                'id' => $table->id,
                'name' => $table->name,
                'status' => $table->status,
                'active_order_id' => $table->activeOrder?->id,
                'active_order_status' => $table->activeOrder?->status,
                'active_order_total' => $table->activeOrder?->total,
            ];
        })
        ->values();

    return response()->json($tables);
}
public function open(Request $request, RestaurantTable $table)
{
    $active = $table->activeOrder()->first();
    if ($active) {
        return response()->json([
            'order_id' => $active->id,
            'status' => $active->status,
            'message' => 'Table already has an active order'
        ], 200);
    }

    $order = Order::create([
    'restaurant_table_id' => $table->id,
    'waiter_id' => $request->user()->id, // ✅ el mesero que atendió
    'status' => 'open',
    'subtotal' => 0,
    'tip' => 0,
    'total' => 0,
]);


    $table->update(['status' => 'occupied']);

    return response()->json([
        'order_id' => $order->id,
        'status' => $order->status,
        'message' => 'Table opened'
    ], 201);
}
}
