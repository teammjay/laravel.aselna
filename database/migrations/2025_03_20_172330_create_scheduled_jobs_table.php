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
        Schema::create('scheduled_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('status')->default('idle');
            $table->dateTime('last_run')->nullable();
            $table->dateTime('next_run')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('name');
            $table->index('status');
        });

        Schema::create('job_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('scheduled_jobs');
            $table->string('status');
            $table->text('output')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_jobs');
    }
};
