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
            $table->longText('subject')->change();
            $table->longText('notes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hd_ticket', function (Blueprint $table) {
            $table->string('subject')->change();
            $table->text('notes')->change();
        });
    }
};
