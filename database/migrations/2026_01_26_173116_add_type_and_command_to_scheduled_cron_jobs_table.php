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
            // Type: job, command, or callback
            $table->string('type')->default('job')->after('cron_expression');
            // Command name for command-based jobs
            $table->string('command')->nullable()->after('type');
            // Description for callback jobs
            $table->text('description')->nullable()->after('command');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheduled_cron_jobs', function (Blueprint $table) {
            $table->dropColumn(['type', 'command', 'description']);
        });
    }
};

