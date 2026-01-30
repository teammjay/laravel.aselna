<?php

namespace Database\Seeders\Common;

use App\Models\ScheduledJobs;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FonusSchedulerCronSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jobs = [
            // Shopify Only Sync - Every 2 hours at 10 minutes past
            [
                'name' => 'Shopify Only Sync',
                'class_name' => 'App\Jobs\Sync\FonusCombinedSyncJob',
                'command' => null,
                'cron_expression' => '10 */2 * * *', // every 2 hours at 10 minutes past
                'parameters' => null,
                'store_type' => 'shopify',
                'is_active' => true,
                'type' => 'job',
                'description' => 'Sync Shopify-only products every 2 hours',
            ],
        ];

        foreach ($jobs as $job) {
            ScheduledJobs::updateOrCreate(
                ['name' => $job['name']],
                [
                    'class_name' => $job['class_name'],
                    'cron_expression' => $job['cron_expression'],
                    'type' => $job['type'],
                    'command' => $job['command'] ?? null,
                    'description' => $job['description'] ?? null,
                    'parameters' => isset($job['parameters']) ? json_encode($job['parameters']) : null,
                    'store_type' => $job['store_type'],
                    'is_active' => $job['is_active'],
                ]
            );
        }

        $this->command->info('Scheduled cron jobs seeded successfully!');
    }
}
