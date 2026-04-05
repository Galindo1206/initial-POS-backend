<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('cash_register_sessions', function (Blueprint $table) {
        $table->id();

        // Quién abrió la caja
        $table->foreignId('opened_by')
              ->constrained('users')
              ->cascadeOnDelete();

        // Quién la cerró (nullable hasta que se cierre)
        $table->foreignId('closed_by')
              ->nullable()
              ->constrained('users')
              ->nullOnDelete();

        $table->timestamp('opened_at')->useCurrent();
        $table->timestamp('closed_at')->nullable();

        // Control de estado rápido
        $table->boolean('is_open')->default(true);

        $table->timestamps();

        // Solo puede haber UNA caja abierta a la vez
        $table->index(['is_open']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_register_sessions');
    }
};
