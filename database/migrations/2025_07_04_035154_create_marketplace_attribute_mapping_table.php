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
        Schema::create('marketplace_attribute_mappings', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id');
            $table->foreign('store_id')->references('id')->on('store')->onDelete('cascade');

            $table->string('code');
            $table->string('value');

            $table->uuid('code_id');

            $table->string('marketplace_code')->nullable();
            $table->timestamps();

            // Create a unique constraint to prevent duplicates
            $table->unique(['store_id', 'code', 'code_id','marketplace_code'], 'unique_marketplace_attribute_mapping');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_attribute_mappings');
    }
};
