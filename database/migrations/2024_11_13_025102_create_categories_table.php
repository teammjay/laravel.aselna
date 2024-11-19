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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            // add name, image, notes1, order1, status, created_by, updated_by
            $table->string('name');
            $table->string('image')->nullable();
            $table->string('notes1')->nullable();
            $table->integer('order')->default(0)->nullable();
            $table->string('code', 50)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by',50)->nullable();
            $table->timestamps();
        });

        Schema::create('categories_sub', function (Blueprint $table) {
            $table->id();
            // add category id, name, image, notes1, order1, status, created_by, updated_by
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('name');
            $table->string('image')->nullable();
            $table->string('notes1')->nullable();
            $table->string('code', 50)->nullable();
            $table->integer('order')->default(0)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('created_by', 50)->nullable();
            $table->string('updated_by',50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories_sub');
        Schema::dropIfExists('categories');
    }
};
