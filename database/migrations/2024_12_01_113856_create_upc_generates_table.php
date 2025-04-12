<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('upc_generates', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('store');
            $table->foreign('store')->references('id')->on('store');
            $table->string('company_prefix');
            $table->string('product_number');
            $table->integer('number_of_codes');
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upc_generates');
    }
};
