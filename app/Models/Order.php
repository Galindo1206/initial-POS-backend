<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    // ✅ Permite asignación masiva (create/update)
    protected $fillable = [
        'restaurant_table_id',
        'waiter_id',
        'status',
        'subtotal',
        'tip',
        'total',
        'paid_at',     // ✅ lo usas en pay()
        'paid_by',     // ✅ lo usas en pay()
    ];

    // ✅ Para que Flutter reciba bien tipos (opcional pero recomendado)
    protected $casts = [
        'subtotal' => 'float',
        'tip' => 'float',
        'total' => 'float',
        'paid_at' => 'datetime',
    ];

    // Mesa asociada (tu FK es restaurant_table_id)
    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    // Mesero que atendió la orden (waiter_id)
    public function waiter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waiter_id');
    }

    // Items de la orden
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Pagos asociados
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Usuario que cobró (paid_by)
    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Recalcula subtotal y total en backend (fuente de verdad).
     * NOTA: aquí haces save(); eso está OK si lo usas intencionalmente.
     * Si prefieres que NO guarde automáticamente, dime y lo cambiamos.
     */
    public function recalcTotals(): void
    {
        $subtotal = (float) $this->items()->sum(DB::raw('qty * unit_price'));
        $this->subtotal = $subtotal;
        $this->total = $subtotal + (float) $this->tip;

        $this->save();
    }
//refund--devoluciones
    public function refunds()
{
    return $this->hasMany(\App\Models\Refund::class);
}

public function refundedTotal(): float
{
    return (float) $this->refunds()->sum('amount');
}

public function refundableAmount(): float
{
    return max(0, (float)$this->total - $this->refundedTotal());
}

}
