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
        Schema::table('listing_item', function (Blueprint $table) {
            $table->string('variant_id')->nullable()->after('store_sku');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listing_item', function (Blueprint $table) {
            $table->dropColumn('variant_id');
        });
    }
};
