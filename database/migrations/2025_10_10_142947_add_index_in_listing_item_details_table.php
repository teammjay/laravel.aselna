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
            $table->index('kicked','idx_listing_item_detail_kicked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

    }
};
