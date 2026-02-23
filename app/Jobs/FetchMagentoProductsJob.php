<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ManageMarketplaceProduct;
use App\Models\MarketplaceAttributeMapping;
use App\Models\Store;
use App\Services\Auth\MagentoAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Jobs\Concerns\TrackableJob;

class FetchMagentoProductsJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 30000;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Magento Products']);

        Log::info('Magento Products Fetch Job Started');

        // Get all Magento stores
        $stores = Store::select('store.*')
            ->with('fileSystem', 'storeType')
            ->join('store_type', 'store_type.id', '=', 'store.store_type_id')
            ->where('store_type.code', 'magento')
            ->where('store.store_status','<>',0)
            ->get();

        if (count($stores ?? []) == 0) {
            Log::info('No Magento stores found');
            return;
        }

        foreach ($stores as $store) {
            Log::info('Fetching products from store: ' . $store->name);
            $this->fetchProductsFromStore($store);
        }

        Log::info('Magento Products Fetch Job Completed');
        $this->updateTrackingRecord([
            'status' => 'completed',
            'finished_at' => now()
        ]);
    }

    /**
     * Fetch products from a specific store
     */
    private function fetchProductsFromStore(Store $store): void
    {
        try {
            $magentoAuthService = new MagentoAuthService($store->seller_id, $store->secret_key,$store->service_url);

            // Get products with pagination
            $currentPage = 1;
            $pageSize = 50;
            $hasMoreProducts = true;

            while ($hasMoreProducts) {
                $products = $this->getProductsFromMagento($magentoAuthService, $pageSize, $currentPage);

                if (empty($products['items']) || count($products['items']) == 0) {
                    $hasMoreProducts = false;
                    continue;
                }

                foreach ($products['items'] as $product) {
                    Log::info('Processing product: ' . json_encode($product));
                    $this->saveProductToDatabase($product, $store);
                }

                Log::info("Processed {$currentPage} page of products from {$store->name}, found " . count($products['items']) . " products");

                if (count($products['items']) < $pageSize) {
                    $hasMoreProducts = false;
                } else {
                    $currentPage++;
                }
            }
        } catch (\Throwable $e) {

            $extraInfo = [
                'store_name' => $store->name
            ];

            \App\Helpers\ErrorLogger::log($e, __METHOD__, $extraInfo);

            CriticalErrorService::report('Fetch Magento Products Job Failed', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],$extraInfo);

            Log::error('Error fetching products from store: ' . $store->name . ', Error: ' . $e->getMessage());
        }
    }

    /**
     * Get products from Magento with pagination
     */
    private function getProductsFromMagento(MagentoAuthService $authService, $pageSize = 50, $currentPage = 1): array
    {
        try {
            return $authService->getProducts($pageSize, $currentPage);
        } catch (\Throwable $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            CriticalErrorService::report('Error getting products from Magento', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error getting products from Magento: ' . $e->getMessage());
            return ['items' => []];
        }
    }

    /**
     * Save product to database
     */
    private function saveProductToDatabase(array $product, Store $store): void
    {
        try {
            $attributes = $this->getProductAttributes($product);

            $productTitle = $product['name'] ?? '';
            $sku = $product['sku'] ?? '';
            $skuPart = explode('-', $sku);
            $product_code = $skuPart[0];
            Log::info('Processing Product Code: ' . $product_code . ' for Store: ' . $store->name);
            $productDetails = Catalog::whereLike('product_sku', $product_code)->get();
            if (count($productDetails) > 1) {
                Log::warning('Multiple products found for SKU: ' . $product_code . ' in Store: ' . $store->name);
                foreach ($productDetails as $detail) {
                    Log::info('Product ID: ' . $detail->product_id . ' - SKU: ' . $detail->product_sku);
                    //match title by starting keyword.
                    $firstWord = explode(' ', $productTitle)[0];
                    Log::info('First Word from Title: ' . $firstWord);
                    if (str_starts_with($detail->product_title, $firstWord)) {
                        Log::info('Matched Product: ' . $detail->product_sku . ' with Title: ' . $productTitle);
                        $productDetail = $detail;
                        break; // Use the first matching product
                    }
                }
            } else {
                $productDetail = $productDetails->first();
            }

            if (!$productDetail) {
                Log::warning('Product not found for SKU: ' . $product_code . ' in Store: ' . $store->name);
                return;
            }

            $marketPlaceDevice = MarketplaceAttributeMapping::where('store_id', $store->id)
                ->where('marketplace_code', $attributes['devices'])
                ->where('code', 'devices')
                ->first();

            if ($product['type_id'] == 'configurable') {
                $product_id = $product['id'] ?? null;
            } else {
                $variant_id = $product['id'] ?? null;
            }

            Log::info('Product :: ' . json_encode($product));
            ManageMarketplaceProduct::updateOrCreate(
                [
                    'product_id' => $productDetail->product_id,
                    'product_code' => $product_code,
                    'sku' => $product['sku'],
                    'store_id' => $store->id
                ],
                [
                    'store_id' => $store->id,
                    'product_id' => $productDetail->product_id,
                    'product_code' => $product_code,
                    'sku' => $sku,
                    'marketplace_product_id' => $product_id ?? null,
                    'marketplace_variant_id' => $variant_id ?? null,
                    'device_id' => $marketPlaceDevice->code_id ?? null,
                    'upload_status' => 'success',
                    'upload_attempts' => 1
                ]
            );
        } catch (\Throwable $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            CriticalErrorService::report('Error saving magento product to database', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error saving product to database: ' . $e->getMessage());
            Log::error('Product data: ' . json_encode($product));
        }
    }

    private function getProductAttributes(array $product)
    {
        $attributes = [];
        if (isset($product['custom_attributes']) && is_array($product['custom_attributes'])) {
            foreach ($product['custom_attributes'] as $attribute) {
                if (isset($attribute['attribute_code']) && isset($attribute['value'])) {
                    $attributes[$attribute['attribute_code']] = $attribute['value'];
                }
            }
        }
        return $attributes;
    }
}
