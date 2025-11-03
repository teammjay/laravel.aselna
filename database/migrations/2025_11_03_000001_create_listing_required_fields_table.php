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
        Schema::create('listing_required_fields', function (Blueprint $table) {
            $table->id();
            $table->string('parent_field', 100);
            $table->string('parent_value', 255);
            $table->string('dependent_field', 100);
            $table->string('category')->nullable();
            $table->uuid('store_type_id');
            $table->foreign('store_type_id')->references('id')->on('store_type');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['parent_field', 'parent_value']);
            $table->index(['dependent_field']);
            $table->index(['category', 'store_type_id']);
            $table->index(['is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_required_fields');
    }
};
