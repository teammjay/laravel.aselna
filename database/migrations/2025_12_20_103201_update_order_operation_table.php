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
        Schema::table('order_operation', function (Blueprint $table) {
            $table->unsignedTinyInteger('operation_retry_count')->default(0);
            $table->text('operation_last_error')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_operation', function (Blueprint $table) {
            $table->dropColumn('operation_retry_count');
            $table->dropColumn('operation_last_error');
        });
    }
};
