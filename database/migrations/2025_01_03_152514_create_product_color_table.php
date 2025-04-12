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
        Schema::create('product_color', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code');
            $table->string('short_code')->nullable();
            $table->uuid('product_type_id')->nullable();
            $table->foreign('product_type_id')->references('id')->on('product_type')
                ->onDelete('cascade');
            $table->uuid('client_id')->nullable();
            $table->uuid('channel_mapping_id')->nullable();
            $table->integer('position_')->nullable();
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
        Schema::dropIfExists('product_color');
    }
};
