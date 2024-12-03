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
        Schema::create('listing_generation_type_template', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('template',500);
            $table->boolean('default_template')->default(0);
            $table->uuid('generation_type_id');
            $table->foreign('generation_type_id')->references('id')->on('listing_generation_type')
                ->onDelete('cascade');
            $table->uuid('client')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listing_generation_type_template');
    }
};
