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
        Schema::create('hd_ticket_hd_ticket_label_link', function (Blueprint $table) {
            $table->uuid('hd_ticket_label_id');
            $table->foreign('hd_ticket_label_id')->references('id')->on('hd_ticket_label')->onDelete('cascade');
            $table->uuid('hd_ticket_id');
            $table->foreign('hd_ticket_id')->references('id')->on('hd_ticket')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hd_ticket_hd_ticket_label_link');
    }
};
