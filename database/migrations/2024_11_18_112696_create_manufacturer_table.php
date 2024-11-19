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
        Schema::create('manufacturer', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code');
            $table->string('notes')->nullable();
            $table->uuid('image_id')->nullable();
            $table->uuid('thumbnail_id')->nullable();
            $table->uuid('client');
            $table->integer('sort_order');
            $table->integer('version');
            $table->string('created_by');
            $table->string('updated_by');
            $table->string('deleted_by');
            $table->string('deleted_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacturer');
    }
};
