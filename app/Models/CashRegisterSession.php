<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashRegisterSession extends Model
{
    protected $fillable = [
        'opened_by',
        'closed_by',
        'opened_at',
        'closed_at',
        'is_open',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'is_open'   => 'boolean',
    ];

    // Scope para obtener la caja abierta actual
    public function scopeOpen($query)
    {
        return $query->where('is_open', true);
    }

    // Relación con usuario que abrió
    public function opener()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    // Relación con usuario que cerró
    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
