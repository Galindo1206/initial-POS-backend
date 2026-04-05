<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
{
    Schema::create('orders', function (Blueprint $table) {
        $table->id();

        $table->foreignId('restaurant_table_id')->constrained('restaurant_tables');
        $table->foreignId('waiter_id')->constrained('users');

        $table->enum('status', ['open','sent','paid','canceled'])->default('open');

        $table->decimal('subtotal', 10, 2)->default(0);
        $table->decimal('tip', 10, 2)->default(0);
        $table->decimal('total', 10, 2)->default(0);

        $table->timestamps();

        $table->index(['restaurant_table_id','status']);
    });
}

};
