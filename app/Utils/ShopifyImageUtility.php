<?php

namespace App\Utils;

use App\Models\ProductImageAlternative;
use App\Models\ProductImages;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Decides which file a Shopify store pushes for a product image, the way oydera's
 * ImageLoader.assignImagesForMarketplaceProductEntity does.
 *
 * A store with store.product_alt_image_number = N > 0 pushes the N-th alternate of the main
 * image (by position, ordered by version_number) when it has at least N alternates, and the
 * original otherwise. Only the main image is ever swapped; every other image pushes the
 * original. (oydera swapped any image with alternates, but only main images have them.)
 *
 * Like oydera, which alternate was pushed is not what decides a re-push: changing N only
 * affects images pushed from then on (new images, or images/alternates updated later).
 */
class ShopifyImageUtility
{
    /**
     * Alternates per main image id, so a run does not re-read them for every store.
     */
    private static array $alternates = [];

    /**
     * The file to push for one product image on one store.
     *
     * @return array{file: \App\Models\SysFile|null, alternate: ProductImageAlternative|null, alt_product_image_id: string|null}
     */
    public static function resolveImage(ProductImages $productImage, Store $store): array
    {
        $original = [
            'file' => $productImage->imageObj,
            'alternate' => null,
            'alt_product_image_id' => null,
        ];

        $n = $store->altImageNumber();
        if ($n <= 0 || $productImage->main_image != 1) {
            return $original;
        }

        $alternate = self::pickAlternate(self::alternatesOf($productImage->id), $n);
        if (!$alternate) {
            return $original;
        }

        if (!$alternate->imageObj) {
            Log::info('ShopifyImageUtility: alternate ' . $alternate->id . ' of image ' . $productImage->id . ' has no file; using the original.');

            return $original;
        }

        return [
            'file' => $alternate->imageObj,
            'alternate' => $alternate,
            'alt_product_image_id' => $alternate->id,
        ];
    }

    /**
     * Only the alternate id resolveImage() would pick, without loading any file.
     */
    public static function resolveAltImageId(ProductImages $productImage, Store $store): ?string
    {
        return self::resolveImage($productImage, $store)['alt_product_image_id'];
    }

    /**
     * The N-th alternate (1-based) of an ordered list, or null when there are fewer than N.
     */
    public static function pickAlternate(Collection $alternates, int $n): ?ProductImageAlternative
    {
        if ($n <= 0) {
            return null;
        }

        return $alternates->values()->get($n - 1);
    }

    public static function flushCache(): void
    {
        self::$alternates = [];
    }

    private static function alternatesOf(string $mainImageId): Collection
    {
        if (!isset(self::$alternates[$mainImageId])) {
            // Keep the cache bounded in long-running jobs.
            if (count(self::$alternates) > 5000) {
                self::$alternates = [];
            }

            self::$alternates[$mainImageId] = ProductImageAlternative::with('imageObj')
                ->where('main_image_id', $mainImageId)
                ->orderBy('version_number')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        return self::$alternates[$mainImageId];
    }
}
