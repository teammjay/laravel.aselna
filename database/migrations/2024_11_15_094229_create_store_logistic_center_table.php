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
        Schema::create('store_logistic_center', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('store_id');
            $table->foreign('store_id')->references('id')->on('store')
                ->onDelete('cascade');
            $table->uuid('logistic_center_id');
            $table->foreign('logistic_center_id')->references('id')->on('logistic_center')
                ->onDelete('cascade');
            $table->boolean('overwrite_from_address');
            $table->boolean('overwrite_return_address');
            $table->uuid('from_address_id')->nullable();
            $table->uuid('return_address_id')->nullable();
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
        Schema::dropIfExists('store_logistic_center');
    }
};
