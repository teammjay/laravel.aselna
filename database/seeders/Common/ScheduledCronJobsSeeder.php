<?php

namespace Database\Seeders\Common;

use App\Models\ScheduledJobs;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ScheduledCronJobsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jobs = [
            // Log rotation
            [
                'name' => 'Log Rotation',
                'class_name' => null, // Command based job
                'command' => 'logs:rotate',
                'cron_expression' => '0 */4 * * *', // every 4 hours
                'parameters' => null,
                'store_type' => null,
                'is_active' => true,
                'type' => 'command',
                'description' => 'Rotate and clean up log files every 4 hours',
            ],
            [
                'name' => 'Daily Update Listing Sales',
                'class_name' => null,
                'command' => 'app:run-daily-update-listing-sales',
                'cron_expression' => '0 */4 * * *', // every 4 hours
                'parameters' => null,
                'store_type' => null,
                'is_active' => true,
                'type' => 'command',
                'description' => 'Update listing sales data every 4 hours',
            ],

            // Listing sync Jobs - Hourly
            [
                'name' => 'Amazon Listing Item Sync',
                'class_name' => 'App\Jobs\AmazonListingItemSyncJob',
                'command' => null,
                'cron_expression' => '0 * * * *', // hourly
                'parameters' => null,
                'store_type' => 'amazon',
                'is_active' => true,
                'type' => 'job',
                'description' => 'Synchronize Amazon listing items hourly',
            ],
            [
                'name' => 'Ebay Listing Item Sync',
                'class_name' => 'App\Jobs\EbayListingItemSyncJob',
                'command' => null,
                'cron_expression' => '0 * * * *', // hourly
                'parameters' => null,
                'store_type' => 'ebay',
                'is_active' => true,
                'type' => 'job',
                'description' => 'Synchronize eBay listing items hourly',
            ],
            [
                'name' => 'Walmart Listing Item Sync',
                'class_name' => 'App\Jobs\WalmartListingItemSyncJob',
                'command' => null,
                'cron_expression' => '0 * * * *', // hourly
                'parameters' => null,
                'store_type' => 'walmart',
                'is_active' => true,
                'type' => 'job',
                'description' => 'Synchronize Walmart listing items hourly',
            ],

            // Existing Listing Item Sync - Daily at 01:00
            [
                'name' => 'Walmart Existing Listing Item Sync',
                'class_name' => 'App\Jobs\Walmart\ExistingListingItemSyncJob',
                'command' => null,
                'cron_expression' => '0 1 * * *', // daily at 01:00
                'parameters' => null,
                'store_type' => 'walmart',
                'is_active' => true,
                'type' => 'job',
                'description' => 'Sync existing Walmart listing items daily at 01:00',
            ],
            [
                'name' => 'Ebay Existing Listing Item Sync',
                'class_name' => 'App\Jobs\Ebay\ExistingListingItemSyncJob',
                'command' => null,
                'cron_expression' => '0 1 * * *', // daily at 01:00
                'parameters' => null,
                'store_type' => 'ebay',
                'is_active' => true,
                'type' => 'job',
                'description' => 'Sync existing eBay listing items daily at 01:00',
            ],
            [
                'name' => 'Amazon Existing Listing Item Sync',
                'class_name' => 'App\Jobs\Amazon\ExistingListingItemSyncJob',
                'command' => null,
                'cron_expression' => '0 1 * * *', // daily at 01:00
                'parameters' => null,
                'store_type' => 'amazon',
                'is_active' => true,
                'type' => 'job',
                'description' => 'Sync existing Amazon listing items daily at 01:00',
            ],

            // Listing Feed Result Syncing - Every 5 minutes
            [
                'name' => 'Fetch Result Feed Async',
                'class_name' => 'App\Jobs\FetchResultFeedAsync',
                'command' => null,
                'cron_expression' => '*/5 * * * *', // every 5 minutes
                'parameters' => null,
                'store_type' => null,
                'is_active' => true,
                'type' => 'job',
                'description' => 'Fetch listing feed results every 5 minutes',
            ],

            // Order Syncing - Every 30 minutes
            [
                'name' => 'Fetch Walmart Orders',
                'class_name' => 'App\Jobs\FetchWalmartOrdersJob',
                'command' => null,
                'cron_expression' => '*/30 * * * *', // every 30 minutes
                'parameters' => null,
                'store_type' => 'walmart',
                'is_active' => true,
                'type' => 'job',
                'description' => 'Fetch new Walmart orders every 30 minutes',
            ],
            [
                'name' => 'Fetch Ebay Orders',
                'class_name' => 'App\Jobs\FetchEbayOrders',
                'command' => null,
                'cron_expression' => '*/30 * * * *', // every 30 minutes
                'parameters' => null,
                'store_type' => 'ebay',
                'is_active' => true,
                'type' => 'job',
                'description' => 'Fetch new eBay orders every 30 minutes',
            ],
            [
                'name' => 'Fetch Shopify Orders',
                'class_name' => 'App\Jobs\FetchShopifyOrders',
                'command' => null,
                'cron_expression' => '*/30 * * * *', // every 30 minutes
                'parameters' => null,
                'store_type' => 'shopify',
                'is_active' => true,
                'type' => 'job',
                'description' => 'Fetch new Shopify orders every 30 minutes',
            ],
            [
                'name' => 'Sync All Order Status',
                'class_name' => 'App\Jobs\SyncAllOrderStatusJob',
                'command' => null,
                'cron_expression' => '*/30 * * * *', // every 30 minutes
                'parameters' => null,
                'store_type' => null,
                'is_active' => true,
                'type' => 'job',
                'description' => 'Sync order statuses across all platforms every 30 minutes',
            ],
            [
                'name' => 'Fetch Temu Orders',
                'class_name' => 'App\Jobs\FetchTemuOrders',
                'command' => null,
                'cron_expression' => '*/30 * * * *', // every 30 minutes
                'parameters' => null,
                'store_type' => 'temu',
                'is_active' => true,
                'type' => 'job',
                'description' => 'Fetch new Temu orders every 30 minutes',
            ],

            // Email Message fetching - Every 10 minutes
            [
                'name' => 'Fetch Email Messages',
                'class_name' => 'App\Jobs\FetchEmailMessagesJob',
                'command' => null,
                'cron_expression' => '*/10 * * * *', // every 10 minutes
                'parameters' => null,
                'store_type' => null,
                'is_active' => true,
                'type' => 'job',
                'description' => 'Fetch and process email messages every 10 minutes',
            ],

            // Products Syncing - Every 5 minutes
            [
                'name' => 'Submit Shopify Products',
                'class_name' => 'App\Jobs\SubmitShopifyProductsJob',
                'command' => null,
                'cron_expression' => '*/5 * * * *', // every 5 minutes
                'parameters' => null,
                'store_type' => 'shopify',
                'is_active' => true,
                'type' => 'job',
                'description' => 'Submit products to Shopify every 5 minutes',
            ],

            // Shopify Only Sync - Every 2 hours at 10 minutes past
            [
                'name' => 'Shopify Only Sync',
                'class_name' => 'App\Jobs\Sync\ShopifyOnlySyncJob',
                'command' => null,
                'cron_expression' => '10 */2 * * *', // every 2 hours at 10 minutes past
                'parameters' => null,
                'store_type' => 'shopify',
                'is_active' => true,
                'type' => 'job',
                'description' => 'Sync Shopify-only products every 2 hours',
            ],

            // Shopify Combined Syncing - Daily at 12:30
            [
                'name' => 'Shopify Combined Syncing',
                'class_name' => 'App\Jobs\Sync\ShopifyCombinedSyncing',
                'command' => null,
                'cron_expression' => '30 12 * * *', // daily at 12:30
                'parameters' => null,
                'store_type' => 'shopify',
                'is_active' => true,
                'type' => 'job',
                'description' => 'Run combined Shopify syncing daily at 12:30',
            ],

            // Clear Order Synchronization - Daily at 00:00
            [
                'name' => 'Clear Order Synchronization',
                'class_name' => null,
                'command' => null,
                'cron_expression' => '0 0 * * *', // daily at midnight
                'parameters' => null,
                'store_type' => null,
                'is_active' => true,
                'type' => 'callback',
                'description' => 'Delete OrderSynchronization records older than today',
            ],

            // Transaction Status Check - Hourly
            [
                'name' => 'Transaction Status Check',
                'class_name' => 'App\Jobs\TransactionStatusCheckJob',
                'command' => null,
                'cron_expression' => '0 * * * *', // hourly
                'parameters' => null,
                'store_type' => null,
                'is_active' => true,
                'type' => 'job',
                'description' => 'Check and update transaction statuses hourly',
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

