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
            $table->integer('resubmit_count')->default(0)->nullable()->after('products_rubber_stamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_operation', function (Blueprint $table) {
            $table->dropColumn('resubmit_count');
        });
    }
};
