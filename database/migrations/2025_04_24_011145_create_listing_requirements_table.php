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
        Schema::create('listing_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->enum('type', ['column', 'key_value']);
            $table->string('table');
            $table->string('column')->nullable(); // For 'column' type
            $table->string('key_column')->nullable(); // For 'key_value' type
            $table->string('key_value')->nullable();
            $table->string('value_column')->nullable(); // For 'key_value' type
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_requirements');
    }
};
