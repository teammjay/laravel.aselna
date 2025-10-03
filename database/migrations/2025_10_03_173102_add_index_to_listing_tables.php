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
        Schema::table('listing_item_detail', function (Blueprint $table) {
            $table->index('upc', 'idx_listing_item_detail_upc');
        });

        Schema::table('upc_codes', function (Blueprint $table) {
            $table->index('is_used', 'idx_upc_codes_is_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
