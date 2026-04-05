<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();

            // monto del refund (puede ser parcial o total)
            $table->decimal('amount', 10, 2);

            // motivo obligatorio
            $table->string('reason', 255);

            // quién hizo el refund
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();

            // timestamp del refund
            $table->timestamp('refunded_at')->useCurrent();

            $table->timestamps();

            $table->index(['order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
