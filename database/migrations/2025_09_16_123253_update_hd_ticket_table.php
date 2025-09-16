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
            $table->string('username')->nullable()->after('ticket_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hd_ticket', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
