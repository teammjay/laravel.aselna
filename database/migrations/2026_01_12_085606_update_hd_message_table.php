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
            $table->json('references')->nullable()->after('reply_to_email');
            $table->string('thread_id')->nullable()->after('references');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hd_message', function (Blueprint $table) {
            $table->dropColumn('references');
            $table->dropColumn('thread_id');
        });
    }
};
