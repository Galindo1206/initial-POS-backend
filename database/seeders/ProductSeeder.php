<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('products')->insert([
            ['name' => 'Ceviche', 'price' => 25.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lomo Saltado', 'price' => 28.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Inca Kola', 'price' => 6.00, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
