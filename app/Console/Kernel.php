<?php

namespace App\Console;

use App\Models\OrderSynchronization;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * Note: In Laravel 11+, routes/console.php is preferred for scheduling.
     * This method is kept for backwards compatibility.
     */
    protected function schedule(Schedule $schedule): void
    {
        try {
            $jobs = \App\Models\ScheduledJobs::where('is_active', true)->get();

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

                        $schedule->job(new $class(...array_values($params)))
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

                        $schedule->command($job->command)
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
                            $schedule->call(function () use ($job) {
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
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
