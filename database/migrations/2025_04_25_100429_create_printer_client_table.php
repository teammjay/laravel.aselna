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
        Schema::create('printer_client', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('version')->default(1);
            $table->uuid('client_id')->nullable();
            $table->string('name');
            $table->string('machine_key');
            $table->boolean('active');
            $table->integer('connection_type');
            $table->uuid('dazzle_data_id')->nullable();
            $table->string('deleted_at')->nullable();
            $table->string('deleted_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('printer_client');
    }
};
