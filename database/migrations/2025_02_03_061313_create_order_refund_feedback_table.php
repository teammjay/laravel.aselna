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
        Schema::create('order_refund_feedback', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('store_type_id');
            $table->foreign('store_type_id')->references('id')->on('store_type')->onDelete('cascade');
            $table->string('name');
            $table->string('channel_code');
            $table->boolean('notes_required');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_refund_feedback');
    }
};
