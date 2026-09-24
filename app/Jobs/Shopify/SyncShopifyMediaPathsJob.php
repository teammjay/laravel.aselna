<?php

namespace App\Jobs\Shopify;

use App\Models\ListingItemProductImage;
use App\Models\Store;
use App\Services\Auth\ShopifyAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Shopify processes product media asynchronously: right after productSet / productUpdate
 * the media nodes come back without preview.image.url, so listing_item_product_images.path
 * is stored as null. This job re-reads the product media later and fills in the missing
 * paths, re-queuing itself while Shopify is still processing.
 */
class SyncShopifyMediaPathsJob implements ShouldQueue
{
    use Queueable;

    public $tries = 6;
    public $timeout = 300;

    // Seconds to wait before re-checking media that Shopify is still processing.
    private const RETRY_DELAYS = [60, 120, 300, 600, 900];

    public function __construct(
        public string $listingItemId,
        public string $storeId,
        public string $marketplaceProductId
    ) {
    }

    public function handle(): void
    {
        $store = Store::find($this->storeId);
        if (!$store) {
            return;
        }

        $missing = ListingItemProductImage::where('listing_item_id', $this->listingItemId)
            ->whereNull('path')
            ->whereNotNull('reference_id')
            ->get()
            ->keyBy('reference_id');

        if ($missing->isEmpty()) {
            return;
        }

        $shopifyAuthService = new ShopifyAuthService($store->secret_key, $store->service_url);
        $response = $shopifyAuthService->getProductImages($this->marketplaceProductId);
        $nodes = $response['data']['product']['media']['nodes'] ?? null;

        if ($nodes === null) {
            Log::warning('SyncShopifyMediaPathsJob: could not fetch media for product ' . $this->marketplaceProductId);
            $this->retryLater();
            return;
        }

        $pending = 0;
        foreach ($nodes as $node) {
            if (($node['mediaContentType'] ?? null) != 'IMAGE' || !isset($node['id'])) {
                continue;
            }

            $referenceId = str_replace('gid://shopify/MediaImage/', '', $node['id']);
            $image = $missing->get($referenceId);
            if (!$image) {
                continue;
            }

            $url = $node['preview']['image']['url'] ?? null;
            $status = $node['preview']['status'] ?? null;

            if (!empty($url)) {
                $image->update(['path' => $url]);
            } elseif ($status == 'FAILED') {
                CriticalErrorService::report('Shopify media processing failed', [
                    'marketplace' => 'Shopify',
                    'store' => $store->name,
                    'product' => $this->marketplaceProductId,
                    'media' => $node['id'],
                    'method' => __METHOD__,
                ]);
            } else {
                $pending++;
            }
        }

        if ($pending > 0) {
            Log::info('SyncShopifyMediaPathsJob: ' . $pending . ' media still processing for product ' . $this->marketplaceProductId);
            $this->retryLater();
        }
    }

    private function retryLater(): void
    {
        // Leave the last attempt alone; SyncShopifyMissingMediaPathsJob picks up anything left over.
        if ($this->attempts() >= $this->tries) {
            return;
        }
        $delay = self::RETRY_DELAYS[min($this->attempts(), count(self::RETRY_DELAYS)) - 1];
        $this->release($delay);
    }
}
