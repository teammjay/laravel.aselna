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
        Schema::table('store_type_category', function (Blueprint $table) {
            $table->string('marketplace_code')->nullable()->after('store_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_type_category', function (Blueprint $table) {
            $table->dropColumn('marketplace_code');
        });
    }
};
