<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OrderItemController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\RefundController;
use App\Http\Controllers\Api\RegisterController;


//
// 1) PUBLIC
//
Route::post('/login', [AuthController::class, 'login']);

//
// 2) AUTH (Sanctum)
//
Route::middleware('auth:sanctum')->group(function () {

    // -----------------------------
    // Auth
    // -----------------------------
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ============================================================
    // A) OPERACIÓN (Mesas / Órdenes / Items / Productos)
    // Roles: admin, manager, waiter
    // ============================================================
    Route::middleware('role:admin,manager,waiter')->group(function () {

        // Mesas
        Route::get('/tables', [TableController::class, 'index']);
        Route::post('/tables/{table}/open', [TableController::class, 'open']);

        // Items de una orden
        Route::get('/orders/{order}/items', [OrderItemController::class, 'index']);
        Route::post('/orders/{order}/items', [OrderItemController::class, 'store']);
        Route::put('/order-items/{item}', [OrderItemController::class, 'update']);
        Route::delete('/order-items/{item}', [OrderItemController::class, 'destroy']);

        // Productos (para tomar orden)
        Route::get('/products', [ProductController::class, 'index']);
    });

    // ============================================================
    // B) COBROS / CAJA
    // Roles: admin, manager, cashier, waiter
    // Nota: "show" sirve para caja buscar por Order ID (Opción A).
    // ============================================================
    Route::middleware('role:admin,manager,cashier')->group(function () {
    Route::post('/orders/{order}/pay', [OrderController::class, 'pay']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::get('/sales/history', [ReportController::class, 'history']);
    Route::get('/sales/{order}', [ReportController::class, 'saleDetail']);
});

    // ============================================================
    // // ============================================================
// C) DEVOLUCIONES / REFUNDS
//    Roles: admin, manager (y si quieres cashier también)
// ============================================================
Route::middleware('role:admin,manager,cashier')->group(function () {
    Route::post('/orders/{order}/refund', [RefundController::class, 'refund']);
    Route::get('/orders/{order}/refunds', [RefundController::class, 'list']);
});


    // ============================================================
    // D) REPORTES (EXCLUSIVO ADMIN)
    // ============================================================
    Route::middleware('role:admin')->group(function () {
        Route::get('/reports/daily-sales', [ReportController::class, 'dailySales']);
        Route::get('/reports/sales-range', [ReportController::class, 'salesRange']);
        Route::get('/reports/sales/summary', [ReportController::class, 'salesSummary']);
        Route::get('/reports/sales', [ReportController::class, 'sales']);
    });

    // ============================================================
    // E) COCINA
    // Enviar a cocina: admin, manager, waiter
    // Ver/gestionar tickets: admin, manager, kitchen
    // ============================================================

    // Enviar orden a cocina (por ejemplo: set sent_to_kitchen_at + status)
    Route::middleware('role:admin,manager,waiter')->group(function () {
        Route::post('/kitchen/orders/{order}/send', [KitchenController::class, 'sendOrder']);
    });

    // Cocina ve tickets y cambia estados (queued/preparing/ready)
    Route::middleware('role:admin,manager,kitchen')->group(function () {
        Route::get('/kitchen/tickets', [KitchenController::class, 'tickets']);
        Route::get('/kitchen/orders', [KitchenController::class, 'index']);
        Route::get('/kitchen/orders/{order}', [KitchenController::class, 'show']);
        Route::patch('/kitchen/items/{item}', [KitchenController::class, 'updateItem']);
        Route::post('/kitchen/orders/{order}/ready', [KitchenController::class, 'markReady']);
    });

        // ============================================================
    // F) CASH REGISTER (APERTURA / CIERRE DE CAJA)
    // ============================================================

    // Ver estado (cualquier usuario autenticado puede consultarlo)
    Route::get('/register/status', [RegisterController::class, 'status']);

    // Abrir / Cerrar caja (solo cashier, manager, admin)
    Route::middleware('role:admin,manager,cashier')->group(function () {
        Route::post('/register/open', [RegisterController::class, 'open']);
        Route::post('/register/close', [RegisterController::class, 'close']);
    });

});
