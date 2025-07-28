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
        Schema::table('hd_message', function (Blueprint $table) {
            $table->longText('subject')->change();
            $table->longText('message')->change();
            $table->longText('previous_message')->nullable()->change();
            $table->longText('links')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hd_message', function (Blueprint $table) {
            $table->string('subject')->change();
            $table->text('message')->change();
            $table->text('previous_message')->change();
            $table->text('links')->change();
        });
    }
};
