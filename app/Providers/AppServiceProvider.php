<?php

namespace App\Providers;


use App\Models\JobStatus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Str;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Opcodes\LogViewer\Facades\LogViewer;
use App\Models\User;
use Opcodes\LogViewer\LogFolder;
use Illuminate\Support\Facades\Gate;
use App\Jobs\Concerns\TrackableJob;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Schema::defaultStringLength(225);

        LogViewer::auth(function ($request) {
            return $request->user()
                && in_array($request->user()->email, [
                    'admin@example.com',
                ]);
        });
        Gate::define('downloadLogFolder', function (?User $user, LogFolder $folder) {
            // return true if the user is allowed to download the whole folder.
            return true;
        });
        // DB::connection()->enableQueryLog();
        DB::listen(function ($query) {
            if (strtoupper(substr($query->sql, 0, 6)) === 'DELETE') {
                $logFile = storage_path('logs/delete_queries.log'); // Separate log file
                $monolog = new Logger('DELETE_SQL');
                $monolog->pushHandler(new StreamHandler($logFile, Logger::INFO));

                // Capture call stack to log class history
                $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10); // Limit to 10 frames for efficiency
                $classHistory = [];

                foreach ($backtrace as $trace) {
                    if (isset($trace['class'])) {
                        $classHistory[] = $trace['class'] . (isset($trace['function']) ? '::' . $trace['function'] : '');
                    }
                }

                // Prepare log message
                $logMessage = '[' . now() . '] ' . $query->sql .
                    ' | Bindings: ' . json_encode($query->bindings) .
                    ' | Time: ' . $query->time . 'ms' .
                    ' | Class History: ' . implode(' -> ', array_reverse($classHistory));

                $monolog->info($logMessage);
            } else if (env('APP_ENV') === 'local') {
                $logFile = storage_path('logs/queries.log'); // Main log file
                $monolog = new Logger('SQL');
                $monolog->pushHandler(new StreamHandler($logFile, Logger::INFO));

                // Prepare log message
                $logMessage = '[' . now() . '] ' . $query->sql .
                    ' | Bindings: ' . json_encode($query->bindings) .
                    ' | Time: ' . $query->time . 'ms';

                $monolog->info($logMessage);
            }
        });


        // Before a job is pushed to the queue
        Queue::createPayloadUsing(function ($connection, $queue, $payload) {
            $job = $payload['data']['command'];
            Log::info('JOB Detail:'.json_encode($job));
            Log::info('Payload Detail:'.json_encode($payload));
            // Check if the job uses the TrackableJob trait
            if (in_array(TrackableJob::class, class_uses_recursive($job))) {
                // Manually create the tracking record here
                $extra = [];

                if (property_exists($job, 'store_name')) {
                    $extra['store_name'] = $job->store_name;
                }
                if (property_exists($job, 'store_type')) {
                    $extra['store_type'] = $job->store_type;
                }

//                $jobData = json_decode($job);
                $jobId = $job->getJobId() ?? (string) Str::uuid();

                Log::info('Extra Detail:'.json_encode($extra));

                /*if (!isset($job->parentJobId)){
                    $job->createTrackingRecord($payload['uuid'], $extra);
                }*/

                $job->createTrackingRecord($jobId, $extra);
            }
            return [];
        });

        // When a worker starts processing a job
        Queue::before(function (JobProcessing $event) {
            $uuid = $event->job->uuid ?? $event->job->payload()['uuid'] ?? null;
//            JobStatus::where('job_id', $uuid)->update([
//                'status' => 'processing',
//                'started_at' => now(),
//            ]);
        });

        // When a job has completed successfully
        Queue::after(function (JobProcessed $event) {
            $uuid = $event->job->uuid ?? $event->job->payload()['uuid'] ?? null;
            Log::info('Complete Job Payload:'.json_encode($event->job->payload()));
//            JobStatus::where('job_id', $uuid)->update([
//                'status' => 'completed',
//                'finished_at' => now(),
//            ]);
        });

        // When a job has failed
        Queue::failing(function (JobFailed $event) {
            $uuid = $event->job->uuid ?? $event->job->payload()['uuid'] ?? null;

            $errorMessage = $event->exception->getMessage();
            $errorFile = $event->exception->getFile();
            $errorLine = $event->exception->getLine();

//            JobStatus::where('job_id', $uuid)->update([
//                'status' => 'failed',
//                'output' => $errorMessage .' in '. $errorFile .' at line '. $errorLine,
//                'finished_at' => now(),
//            ]);
        });
    }
}
