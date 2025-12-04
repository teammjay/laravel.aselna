<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledJobs;
use Illuminate\Support\Str;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class SyncJobRegistryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:sync-registry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan app/Jobs and sync all job classes into the scheduled_cron_jobs table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = app_path('Jobs');
        $namespace = 'App\\Jobs\\';
        $foundJobs = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace([$path . DIRECTORY_SEPARATOR, '.php'], '', $file->getPathname());
                $class = $namespace . str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

                if (class_exists($class)) {
                    $foundJobs[] = $class;
                }
            }
        }

        $this->info("Found " . count($foundJobs) . " job classes.");

        foreach ($foundJobs as $class) {
            $exists = ScheduledJobs::where('class_name', $class)->exists();

            if (! $exists) {
                ScheduledJobs::create([
                    'name' => class_basename($class),
                    'class_name' => $class,
                    'cron_expression' => '* * * * *',
                    'is_active' => false,
                ]);
                $this->info("Added: {$class}");
            }
        }

        $this->info("Job registry synced successfully!");
    }
}
