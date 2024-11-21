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
        Schema::create('device_sub_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Picture, notes1 and name
            // also add foreign key to device_types
            $table->string('name', 30);
            $table->text('notes1')->nullable();
            $table->uuid('device_type_id')->nullable();
            $table->foreign('device_type_id')->references('id')->on('device_types')
                ->onDelete('cascade');
            $table->unique(['name', 'device_type_id']);
            $table->index('device_type_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_sub_types');
    }
};
