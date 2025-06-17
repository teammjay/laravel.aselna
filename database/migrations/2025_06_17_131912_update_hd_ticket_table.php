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
        Schema::table('hd_ticket', function (Blueprint $table) {
            $table->dateTime('ticket_date')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hd_ticket', function (Blueprint $table) {
            $table->timestamp('ticket_date')->change();
        });
    }
};
