<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('restaurant_tables', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Mesa 1, Mesa 2
        $table->enum('status', ['free', 'occupied'])->default('free');
        $table->timestamps();
    });
}

};
