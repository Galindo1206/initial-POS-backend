<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        // Devuelve productos para el POS
        return Product::select('id', 'name', 'price', 'send_to_kitchen')
    ->orderBy('name')
    ->get();
    }
}
