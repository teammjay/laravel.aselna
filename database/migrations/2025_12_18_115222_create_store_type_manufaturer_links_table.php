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
        Schema::create('store_type_manufaturer_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('store_type_id');
            $table->uuid('manufacturer_id');
            $table->string('manufacturer_name');
            $table->string('manufacturer_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_type_manufaturer_links');
    }
};
