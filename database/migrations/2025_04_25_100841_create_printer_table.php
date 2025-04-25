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
        Schema::create('printer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_id')->nullable();
            $table->string('name');
            $table->integer('label_format');
            $table->string('status_message',1024)->nullable();
            $table->string('internal_name',1024)->nullable();
            $table->integer('dpi');
            $table->uuid('printer_client_id')->nullable();
            $table->foreign('printer_client_id')->references('id')->on('printer_client')->onDelete('cascade');
            $table->integer('status');
            $table->integer('printer_type')->default(0);
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
        Schema::dropIfExists('printer');
    }
};
