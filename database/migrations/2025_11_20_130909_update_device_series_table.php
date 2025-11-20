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
        Schema::create('device_series_search_replace_text', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('device_series_id');
            $table->text('replace_search_text');
            $table->text('replace_value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_series_search_replace_text');
    }
};
