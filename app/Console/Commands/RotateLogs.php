<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class RotateLogs extends Command
{
    protected $signature = 'logs:rotate';
    protected $description = 'Rotate logs to dated folders every 6 hours';

    public function handle()
    {
        $currentLogPath = storage_path('logs/current/laravel.log');
        
        if (!File::exists($currentLogPath)) {
            $this->info('No logs to rotate.');
            return;
        }

        $now = Carbon::now();
        $timeSlot = floor($now->hour / 6) * 6;
        $timeRange = sprintf('%02d-%02d', $timeSlot, $timeSlot + 5);
        
        $targetDir = storage_path(sprintf(
            'logs/archived/%s/%s',
            $now->format('Y-m-d'),
            $timeRange
        ));

        if (!File::exists($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        $targetPath = $targetDir . '/laravel-' . $now->format('Y-m-d-His') . '.log';
        
        // Move the current log file
        File::move($currentLogPath, $targetPath);
        
        // Create a new empty log file
        File::put($currentLogPath, '');
        
        $this->info('Logs rotated successfully.');
    }
}
