<?php

use App\Models\OrderSynchronization;
use App\Models\ScheduledJobs;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/**
 * Load all scheduled jobs from the database
 * Jobs are managed in the scheduled_cron_jobs table
 * Use ScheduledCronJobsSeeder to seed initial jobs
 *
 * Supports three types of scheduled tasks:
 * - job: Laravel Queue Jobs (class_name required)
 * - command: Artisan Commands (command required)
 * - callback: Inline PHP callbacks (handled specially)
 */
try {
    $jobs = ScheduledJobs::where('is_active', true)->get();

    foreach ($jobs as $job) {
        $type = $job->type ?? 'job';

        switch ($type) {
            case 'job':
                // Skip if no class_name for job type
                if (empty($job->class_name)) {
                    continue 2;
                }

                $class = $job->class_name;

                if (!class_exists($class)) {
                    Log::warning("Scheduled job class not found: {$class}");
                    continue 2;
                }

                $params = json_decode($job->parameters, true) ?? [];

                Schedule::job(new $class(...array_values($params)))
                    ->cron($job->cron_expression)
                    ->name($job->name)
                    ->after(function () use ($job) {
                        $job->update([
                            'last_run_at' => now(),
                            'last_status' => 'success',
                        ]);
                    })
                    ->onFailure(function () use ($job) {
                        $job->update([
                            'last_run_at' => now(),
                            'last_status' => 'failed',
                        ]);
                    });
                break;

            case 'command':
                // Skip if no command for command type
                if (empty($job->command)) {
                    continue 2;
                }

                Schedule::command($job->command)
                    ->cron($job->cron_expression)
                    ->name($job->name)
                    ->after(function () use ($job) {
                        $job->update([
                            'last_run_at' => now(),
                            'last_status' => 'success',
                        ]);
                    })
                    ->onFailure(function () use ($job) {
                        $job->update([
                            'last_run_at' => now(),
                            'last_status' => 'failed',
                        ]);
                    });
                break;

            case 'callback':
                // Handle specific callbacks based on the job name
                if ($job->name === 'Clear Order Synchronization') {
                    Schedule::call(function () use ($job) {
                        OrderSynchronization::where('updated_at', '<', now()->toDate())->delete();
                        $job->update([
                            'last_run_at' => now(),
                            'last_status' => 'success',
                        ]);
                    })
                        ->cron($job->cron_expression)
                        ->name($job->name)
                        ->onFailure(function () use ($job) {
                            $job->update([
                                'last_run_at' => now(),
                                'last_status' => 'failed',
                            ]);
                        });
                }
                break;
        }
    }
} catch (\Exception $e) {
    Log::error("Failed to load scheduled jobs from database: " . $e->getMessage());
}
