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
        Schema::create('upc_codes', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('upc_code');
            $table->string('company_prefix');
            $table->string('product_number');
            $table->boolean('is_used')->default(false);
            $table->integer('sequence_id');
            $table->uuid('store');
            $table->foreign('store')->references('id')->on('store');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upc_codes');
    }
};
