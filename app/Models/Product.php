<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'send_to_kitchen',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'send_to_kitchen' => 'boolean',
    ];
}
