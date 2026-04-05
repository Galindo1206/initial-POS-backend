<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('kitchen_status', 20)->default('draft')->index();
            $table->timestamp('sent_to_kitchen_at')->nullable()->index();
            $table->timestamp('ready_at')->nullable()->index();
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prepared_by');
            $table->dropColumn(['kitchen_status','sent_to_kitchen_at','ready_at']);
        });
    }
};
