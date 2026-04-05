<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Order;

class ReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | ADMIN REPORTS (Gerenciales)
    |--------------------------------------------------------------------------
    | Estas rutas deben estar protegidas por middleware role:admin
    | NO repetir validación de role aquí (evitamos duplicidad).
    |--------------------------------------------------------------------------
    */

    // GET /api/reports/daily-sales?date=YYYY-MM-DD
    public function dailySales(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $date = $data['date'];

        // Base: órdenes pagadas ese día (por paid_at)
        $base = Order::query()
            ->whereDate('orders.paid_at', $date)
            ->where('status', 'paid');

        // Totales
        $totals = (clone $base)->selectRaw("
            COUNT(*) as orders_count,
            COALESCE(SUM(subtotal), 0) as subtotal_sum,
            COALESCE(SUM(tip), 0) as tip_sum,
            COALESCE(SUM(total), 0) as total_sum,
            COALESCE(AVG(total), 0) as avg_ticket
        ")->first();

        // Ventas por método de pago (payments)
        $byMethod = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->whereDate('orders.paid_at', $date)
            ->where('orders.status', 'paid')
            ->groupBy('payments.method')
            ->selectRaw("
                payments.method,
                COUNT(*) as payments_count,
                COALESCE(SUM(payments.amount), 0) as amount_sum
            ")
            ->orderBy('payments.method')
            ->get();

        // Ventas por MESERO QUE COBRÓ (paid_by)
        $byWaiter = (clone $base)
            ->leftJoin('users', 'users.id', '=', 'orders.paid_by')
            ->groupBy('orders.paid_by', 'users.name')
            ->selectRaw("
                orders.paid_by as waiter_id,
                COALESCE(users.name, 'N/A') as waiter_name,
                COUNT(*) as orders_count,
                COALESCE(SUM(orders.total), 0) as total_sum,
                COALESCE(SUM(orders.tip), 0) as tip_sum
            ")
            ->orderByDesc('total_sum')
            ->get();

        return response()->json([
            'date' => $date,
            'totals' => [
                'orders_count' => (int) $totals->orders_count,
                'subtotal_sum' => (float) $totals->subtotal_sum,
                'tip_sum' => (float) $totals->tip_sum,
                'total_sum' => (float) $totals->total_sum,
                'avg_ticket' => (float) $totals->avg_ticket,
            ],
            'by_method' => $byMethod,
            'by_waiter' => $byWaiter,
        ]);
    }

    // GET /api/reports/sales-range?from=YYYY-MM-DD&to=YYYY-MM-DD
    public function salesRange(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = $data['from'];
        $to = $data['to'];

        // Base: pagadas en rango (por paid_at)
        $base = Order::query()
            ->where('status', 'paid')
            ->whereBetween(DB::raw("DATE(paid_at)"), [$from, $to]);

        // Totales
        $totals = (clone $base)->selectRaw("
            COUNT(*) as orders_count,
            COALESCE(SUM(subtotal), 0) as subtotal_sum,
            COALESCE(SUM(tip), 0) as tip_sum,
            COALESCE(SUM(total), 0) as total_sum,
            COALESCE(AVG(total), 0) as avg_ticket
        ")->first();

        // Por día
        $byDay = (clone $base)
            ->selectRaw("
                DATE(paid_at) as day,
                COUNT(*) as orders_count,
                COALESCE(SUM(total), 0) as total_sum,
                COALESCE(SUM(tip), 0) as tip_sum
            ")
            ->groupBy(DB::raw("DATE(paid_at)"))
            ->orderBy('day')
            ->get();

        // Por método
        $byMethod = DB::table('payments')
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('orders.status', 'paid')
            ->whereBetween(DB::raw("DATE(orders.paid_at)"), [$from, $to])
            ->groupBy('payments.method')
            ->selectRaw("
                payments.method,
                COUNT(*) as payments_count,
                COALESCE(SUM(payments.amount), 0) as amount_sum
            ")
            ->orderBy('payments.method')
            ->get();

        // Por MESERO QUE COBRÓ (paid_by)
        $byWaiter = DB::table('orders')
            ->leftJoin('users', 'users.id', '=', 'orders.paid_by')
            ->where('orders.status', 'paid')
            ->whereBetween(DB::raw("DATE(orders.paid_at)"), [$from, $to])
            ->groupBy('orders.paid_by', 'users.name')
            ->selectRaw("
                orders.paid_by as waiter_id,
                COALESCE(users.name, 'N/A') as waiter_name,
                COUNT(*) as orders_count,
                COALESCE(SUM(orders.total), 0) as total_sum,
                COALESCE(SUM(orders.tip), 0) as tip_sum
            ")
            ->orderByDesc('total_sum')
            ->get();

        return response()->json([
            'from' => $from,
            'to' => $to,
            'totals' => [
                'orders_count' => (int) $totals->orders_count,
                'subtotal_sum' => (float) $totals->subtotal_sum,
                'tip_sum' => (float) $totals->tip_sum,
                'total_sum' => (float) $totals->total_sum,
                'avg_ticket' => (float) $totals->avg_ticket,
            ],
            'by_day' => $byDay,
            'by_method' => $byMethod,
            'by_waiter' => $byWaiter,
        ]);
    }

    // GET /api/reports/sales/summary?from=YYYY-MM-DD&to=YYYY-MM-DD
    public function salesSummary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date'],
        ]);

        $from = $data['from'] . ' 00:00:00';
        $to   = $data['to'] . ' 23:59:59';

        $q = Order::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$from, $to]);

        return response()->json([
            'from' => $data['from'],
            'to' => $data['to'],
            'orders_count' => (int) $q->count(),
            'subtotal' => (float) $q->sum('subtotal'),
            'tip' => (float) $q->sum('tip'),
            'total' => (float) $q->sum('total'),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORY (Operativo) - Admin + Waiter
    |--------------------------------------------------------------------------
    | Estas rutas deben estar protegidas por middleware role:admin,waiter
    |--------------------------------------------------------------------------
    */
// GET /api/sales/history?from=YYYY-MM-DD&to=YYYY-MM-DD&waiter=...&from_time=HH:MM&to_time=HH:MM
public function history(Request $request): JsonResponse
{
    $user = $request->user();

    $data = $request->validate([
        'from' => ['required','date'],
        'to' => ['required','date'],
        'from_time' => ['nullable','date_format:H:i'],
        'to_time' => ['nullable','date_format:H:i'],

        // Filtros opcionales (solo admin/manager/cashier podrán usarlos completos)
        'waiter' => ['nullable','string','max:80'],        // nombre
        'waiter_id' => ['nullable','integer','exists:users,id'], // id
    ]);

    $from = $data['from'] . ' ' . ($data['from_time'] ?? '00:00') . ':00';
    $to   = $data['to']   . ' ' . ($data['to_time'] ?? '23:59') . ':59';

    $query = Order::query()
        ->with([
            'table:id,name',
            'payments',
            'waiter:id,name',  // ✅ importante para mostrar quién atendió
            'paidBy:id,name',  // opcional para caja/admin
        ])
        ->where('status', 'paid')
        ->whereBetween('paid_at', [$from, $to]);

    // =========================================================
    // 🔐 BLOQUEO POR ROL
    // =========================================================
    if ($user->role === 'waiter') {
        // ✅ waiter SOLO ve las órdenes que atendió
        $query->where('waiter_id', $user->id);

        // Si quieres, ignoramos cualquier filtro que él mande
        // (no le permitimos "buscar otro waiter")
    } else {
        // admin/manager/cashier pueden filtrar por waiter_id o nombre

        if (!empty($data['waiter_id'])) {
            $query->where('waiter_id', (int) $data['waiter_id']);
        }

        if (!empty($data['waiter'])) {
            $name = $data['waiter'];
            $query->whereHas('waiter', function($q) use ($name) {
                $q->where('name', 'like', '%' . $name . '%');
            });
        }
    }

    return response()->json(
        $query->orderByDesc('paid_at')
              ->get([
                  'id',
                  'restaurant_table_id',
                  'waiter_id',
                  'paid_by',
                  'subtotal',
                  'tip',
                  'total',
                  'paid_at',
              ])
    );
}



    // GET /api/sales/{order}
    public function saleDetail(Order $order): JsonResponse
    {
        return response()->json([
            'order' => $order->load('table', 'items.product', 'payments', 'paidBy'),
        ]);
    }
}
