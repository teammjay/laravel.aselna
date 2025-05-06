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
            $table->boolean('buyer_initiated')->nullable()->change();
            $table->boolean('seller_provides_return_label')->nullable()->change();
            $table->boolean('print_to_file')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_operation', function (Blueprint $table) {
            $table->boolean('buyer_initiated')->change();
            $table->boolean('seller_provides_return_label')->change();
            $table->boolean('print_to_file')->change();
        });
    }
};
