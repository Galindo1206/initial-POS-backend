<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RestaurantTable;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            RestaurantTable::updateOrCreate(
                ['name' => "Mesa $i"],
                ['status' => 'free']
            );
        }
    }
}
