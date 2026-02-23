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
        Schema::table('job_statuses', function (Blueprint $table) {
            $table->uuid('parent_job_id')->nullable()->index()->after('chain_id');
            $table->integer('order')->nullable()->after('parent_job_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_statuses', function (Blueprint $table) {
            $table->dropColumn(['parent_job_id', 'order']);
        });
    }
};
