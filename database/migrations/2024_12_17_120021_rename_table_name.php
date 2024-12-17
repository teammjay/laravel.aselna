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
        Schema::dropIfExists('listing_item');
        Schema::rename('store_type_listing_item', 'listing_item');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //Schema::rename('listing_item', 'store_type_listing_item');
    }
};
