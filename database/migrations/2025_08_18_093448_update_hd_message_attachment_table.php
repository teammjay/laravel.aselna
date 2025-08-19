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
        //
        Schema::table('hd_message_attachment', function (Blueprint $table) {
            $table->uuid('client_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hd_message_attachment', function (Blueprint $table) {
            $table->uuid('client_id')->change();
        });
    }
};
