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
            $table->text('parameters')->nullable()->after('cron_expression');
            $table->timestamp('last_run_at')->nullable()->after('parameters');
            $table->string('last_status')->nullable()->after('last_run_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheduled_cron_jobs', function (Blueprint $table) {
            $table->dropColumn('parameters');
            $table->dropColumn('last_run_at');
            $table->dropColumn('last_status');
        });
    }
};
