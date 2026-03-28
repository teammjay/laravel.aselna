<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\StoreTypeCategory;
use App\Services\Auth\WalmartAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckWalmartCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-walmart-categories {--limit= : Limit the number of categories to check} {--chunk=20 : Number of categories to check in each API request} {--sleep=1 : Seconds to sleep between API calls to avoid rate limits}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check which Walmart categories return errors in the API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $chunkSize = (int) $this->option('chunk');
        $sleepSeconds = (int) $this->option('sleep');
        
        $store = Store::join('store_type', 'store.store_type_id', '=', 'store_type.id')
            ->where('store_type.code', 'walmart')
            ->where('store.store_status', '<>', 0)
            ->whereNotNull('seller_id')
            ->whereNotNull('secret_key')
            ->select('store.*')
            ->first();

        if (!$store) {
            $this->error('No active Walmart store with credentials found.');
            return;
        }

        $this->info("Using store: {$store->name} (ID: {$store->id})");

        $query = StoreTypeCategory::join('store_type', 'store_type_category.store_type_id', '=', 'store_type.id')
            ->where('store_type.code', 'walmart')
            ->select('store_type_category.*')
            ->orderBy('store_type_category.name');

        if ($limit) {
            $query->limit($limit);
        }

        $categories = $query->get();

        if ($categories->isEmpty()) {
            $this->error('No Walmart categories found in store_type_category table.');
            return;
        }

        $totalCategories = $categories->count();
        $this->info("Found {$totalCategories} categories to check.");
        $this->info("Grouping in chunks of {$chunkSize}...");

        $authService = new WalmartAuthService($store->seller_id, $store->secret_key);

        $validCategories = [];
        $invalidCategories = [];
        $totalProcessed = 0;

        foreach ($categories->chunk($chunkSize) as $chunk) {
            $names = $chunk->pluck('name')->toArray();
            $countInChunk = count($names);
            $totalProcessed += $countInChunk;
            
            $this->info("Processing chunk: {$totalProcessed}/{$totalCategories} (" . implode(', ', $names) . ")...");

            try {
                // Walmart Specific version used in WalmartService
                $version = '5.0.20250828-17_32_59-api';
                $response = $authService->getProductSpecifications('MP_ITEM', $version, $names);
                
                $specMap = $response['schema']['properties']['MPItem']['items']['properties']['Visible']['properties'] ?? [];
                
                foreach ($names as $name) {
                    if (isset($specMap[$name])) {
                        $validCategories[] = $name;
                    } else {
                        $reason = 'Missing in API response Visible properties';
                        
                        // Check if the API returned an error message in the response body
                        if (isset($response['errors'])) {
                            $reason = 'API Errors: ' . json_encode($response['errors']);
                        }

                        $invalidCategories[] = [
                            'name' => $name,
                            'reason' => $reason
                        ];
                        
                        $this->warn("INVALID: {$name}");
                    }
                }
            } catch (\Throwable $e) {
                $this->error("API request failed for this chunk: " . $e->getMessage());
                foreach ($names as $name) {
                    $invalidCategories[] = [
                        'name' => $name,
                        'reason' => 'Exception: ' . $e->getMessage()
                    ];
                }
            }

            if ($totalProcessed < $totalCategories && $sleepSeconds > 0) {
                $this->comment("Sleeping for {$sleepSeconds} seconds to respect rate limits...");
                sleep($sleepSeconds);
            }
        }

        $this->info("\n" . str_repeat('=', 40));
        $this->info("FINAL RESULT SUMMARY");
        $this->info(str_repeat('=', 40));
        $this->info("Total Categories Checked: {$totalProcessed}");
        $this->info("Valid Categories:         " . count($validCategories));
        $this->error("Invalid Categories:       " . count($invalidCategories));
        $this->info(str_repeat('-', 40));

        if (!empty($invalidCategories)) {
            $this->error("LIST OF INVALID CATEGORIES:");
            foreach ($invalidCategories as $invalid) {
                $this->line("- {$invalid['name']}");
                $this->line("  Reason: {$invalid['reason']}");
                
                // Also log to the system log for persistence
                Log::error("Invalid Walmart Category identified: name='{$invalid['name']}', reason='{$invalid['reason']}'");
            }
        } else {
            $this->info("All categories checked are currently valid in the Walmart API.");
        }
        
        $this->info(str_repeat('=', 40));
    }
}
