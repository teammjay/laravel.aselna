<?php

namespace App\Console\Commands;

use App\Jobs\ListingItemProductImageSyncJob;
use App\Models\ListingItemProductImage;
use App\Models\Store;
use App\Services\Auth\ShopifyAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Puts back the listing_item_product_images rows the oydera master switch copied over and
 * FetchShopifyProductsJob then deleted again (two Shopify products sharing a sku each delete the
 * other one's images, and rows still holding the oydera REST image id are taken for gone).
 *
 * The source rows are read from oydera (easyesale_db) and the product image is resolved the way
 * ListingItemProductImageSyncJob does it. reference_id and path are not copied from the source:
 * they are taken from the media of the listing item's own Shopify product (listing_item.store_sku),
 * matched on the image file, so the restored rows survive the next FetchShopifyProductsJob run.
 * A source row whose image is no longer on that Shopify product, or that is already here by id,
 * path or media id, is left out.
 *
 * Nothing is written unless --apply is passed.
 */
class RestoreShopifyListingImagesCommand extends Command
{
    protected $signature = 'shopify:restore-listing-images
                            {store : Store name or id}
                            {--sku=* : Only these listing item skus}
                            {--apply : Write the rows; without it this is a dry run}';

    protected $description = 'Restore listing item images deleted after the oydera master switch, from oydera, with the Shopify media ids.';

    public function handle()
    {
        $store = Store::select('store.*')
            ->join('store_type', 'store_type.id', '=', 'store.store_type_id')
            ->where('store_type.code', 'shopify')
            ->where(function ($query) {
                $query->where('store.id', $this->argument('store'))
                    ->orWhere('store.name', $this->argument('store'));
            })
            ->first();

        if (!$store) {
            $this->error('No Shopify store found matching: ' . $this->argument('store'));

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');

        $this->info(($apply ? 'APPLY' : 'DRY RUN') . ' for ' . $store->name . ' (' . $store->id . ')');

        $listingItems = DB::table('listing_item')
            ->join('store_product', 'store_product.id', '=', 'listing_item.store_product_id')
            ->where('store_product.store_id', $store->id)
            ->when($this->option('sku'), fn ($query, $skus) => $query->whereIn('listing_item.sku', $skus))
            ->select('listing_item.id', 'listing_item.sku', 'listing_item.store_sku')
            ->get()
            ->keyBy('id');

        if ($listingItems->isEmpty()) {
            $this->warn('No listing items found.');

            return self::SUCCESS;
        }

        // Source rows that are not here any more, grouped by listing item.
        $missing = collect();

        foreach ($listingItems->keys()->chunk(500) as $chunk) {
            $links = DB::connection('easyesale_db')->table('listing_item_product_image')
                ->join('product_image', 'listing_item_product_image.product_image_id', '=', 'product_image.id')
                ->whereIn('listing_item_product_image.listing_item_id', $chunk->all())
                ->select('listing_item_product_image.*', 'product_image.product_id', 'product_image.code')
                ->get()
                ->unique('id');

            $existingIds = DB::table('listing_item_product_images')
                ->whereIn('id', $links->pluck('id')->all())
                ->pluck('id')
                ->flip();

            $missing = $missing->merge($links->reject(fn ($link) => $existingIds->has($link->id)));
        }

        if ($missing->isEmpty()) {
            $this->info('Every oydera image row is already here. Nothing to restore.');

            return self::SUCCESS;
        }

        $syncJob = new ListingItemProductImageSyncJob($store->id);
        $productImages = $syncJob->resolveProductImages($missing);
        $shopify = new ShopifyAuthService($store->secret_key, $store->service_url);

        $rows = [];
        $toInsert = [];

        foreach ($missing->groupBy('listing_item_id') as $listingItemId => $links) {
            $listingItem = $listingItems->get($listingItemId);
            $shopifyProductId = strtok((string) $listingItem->store_sku, '-');

            if (empty($shopifyProductId) || !ctype_digit($shopifyProductId)) {
                foreach ($links as $link) {
                    $rows[] = [$listingItem->sku, $link->id, basename(strtok((string) $link->path, '?')), '-', 'skip: listing item has no Shopify product id in store_sku'];
                }
                continue;
            }

            // The file name before "?v=" is what stays the same between oydera and Shopify.
            $media = [];
            $response = $shopify->getProductImages('gid://shopify/Product/' . $shopifyProductId);
            foreach ($response['data']['product']['media']['nodes'] ?? [] as $node) {
                $url = $node['preview']['image']['url'] ?? null;
                if ($node['mediaContentType'] == 'IMAGE' && !empty($url)) {
                    $media[strtok($url, '?')] = [
                        'id' => str_replace('gid://shopify/MediaImage/', '', $node['id']),
                        'url' => $url,
                    ];
                }
            }

            $local = DB::table('listing_item_product_images')
                ->where('listing_item_id', $listingItemId)
                ->get(['path', 'reference_id']);
            $localPaths = $local->pluck('path')->filter()->map(fn ($path) => strtok($path, '?'))->flip();
            $localReferences = $local->pluck('reference_id')->filter()->flip();

            foreach ($links as $link) {
                $file = strtok((string) $link->path, '?');
                $match = $media[$file] ?? null;
                $productImage = $productImages[$link->product_image_id] ?? null;

                if (!$match) {
                    $action = 'skip: image not on Shopify product ' . $shopifyProductId;
                } elseif (!$productImage) {
                    $action = 'skip: no local product image for code ' . $link->code;
                } elseif ($localPaths->has($file) || $localReferences->has($match['id'])) {
                    $action = 'skip: already here';
                } else {
                    $action = 'restore';
                    $toInsert[] = ['id' => $link->id] + array_merge($syncJob->mapImageLink($link, $productImage), [
                        'reference_id' => $match['id'],
                        'path' => $match['url'],
                    ]);
                    // So a second source row for the same file is not restored twice.
                    $localPaths->put($file, true);
                    $localReferences->put($match['id'], true);
                }

                $rows[] = [$listingItem->sku, $link->id, basename($file), $match['id'] ?? '-', $action];
            }
        }

        $this->table(['sku', 'oydera row id', 'file', 'shopify media id', 'action'], $rows);
        $this->info(count($toInsert) . ' row(s) to restore, ' . (count($rows) - count($toInsert)) . ' skipped.');

        if (!$apply) {
            $this->warn('Dry run, nothing written. Re-run with --apply to restore.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($toInsert) {
            foreach ($toInsert as $attributes) {
                $model = new ListingItemProductImage();
                $model->forceFill($attributes);
                $model->save();
            }
        });

        $this->info('Restored ' . count($toInsert) . ' row(s).');

        return self::SUCCESS;
    }
}
