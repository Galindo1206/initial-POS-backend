<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->unsignedInteger('qty')->default(1);

            // snapshot del precio al momento de vender
            $table->decimal('unit_price', 10, 2);

            $table->string('notes')->nullable();

            $table->enum('status', ['pending', 'sent', 'served', 'void'])
                ->default('pending');

            $table->timestamps();

            // evita duplicados del mismo producto en la misma orden
            $table->unique(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
