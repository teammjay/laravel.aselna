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
        Schema::table('scheduled_cron_jobs', function (Blueprint $table) {
            $table->uuid('store_type')->nullable()->after('parameters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheduled_cron_jobs', function (Blueprint $table) {
            $table->dropColumn('store_type');
        });
    }
};
