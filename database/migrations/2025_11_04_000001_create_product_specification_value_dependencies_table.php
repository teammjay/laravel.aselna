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
        Schema::create('product_specification_value_dependencies', function (Blueprint $table) {
            $table->id();
            $table->string('parent_field_name'); // The parent field name
            $table->string('parent_field_value_id'); // The parent field value ID (vid)
            $table->string('child_field_name'); // The child field name
            $table->string('child_field_value_id'); // The child field value ID (vid)
            $table->string('category'); // Category code
            $table->uuid('store_type_id');
            $table->foreign('store_type_id')->references('id')->on('store_type');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for performance
            $table->index(['parent_field_name', 'parent_field_value_id', 'category', 'store_type_id'], 'parent_lookup_idx');
            $table->index(['child_field_name', 'category', 'store_type_id'], 'child_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_specification_value_dependencies');
    }
};
