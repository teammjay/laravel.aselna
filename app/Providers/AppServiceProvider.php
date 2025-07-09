<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Opcodes\LogViewer\Facades\LogViewer;
use App\Models\User;
use Opcodes\LogViewer\LogFolder;
use Illuminate\Support\Facades\Gate;


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
    }
}
