<?php

namespace Tests\Utils;

use App\Models\ProductImageAlternative;
use App\Models\ProductImages;
use App\Models\Store;
use App\Utils\ShopifyImageUtility;
use Tests\TestCase;

/**
 * Only the parts that need no database: picking the alternate and the store setting.
 */
class ShopifyImageUtilityTest extends TestCase
{
    private const IMAGE_ID = '4eed31f3-5514-4778-906e-c612ec735474';

    private function alternates(int $count)
    {
        return collect(range(1, $count))->map(function ($version) {
            $alternate = new ProductImageAlternative();
            $alternate->id = 'alt-' . $version;
            $alternate->version_number = $version;

            return $alternate;
        });
    }

    public function testPickAlternateIsByPosition()
    {
        $alternates = $this->alternates(3);

        $this->assertSame('alt-1', ShopifyImageUtility::pickAlternate($alternates, 1)->id);
        $this->assertSame('alt-3', ShopifyImageUtility::pickAlternate($alternates, 3)->id);
    }

    public function testPickAlternateFallsBackWhenOutOfRange()
    {
        $alternates = $this->alternates(2);

        $this->assertNull(ShopifyImageUtility::pickAlternate($alternates, 3));
        $this->assertNull(ShopifyImageUtility::pickAlternate($alternates, 0));
        $this->assertNull(ShopifyImageUtility::pickAlternate(collect(), 1));
    }

    public function testPickAlternateIgnoresCollectionKeys()
    {
        $alternates = $this->alternates(3)->keyBy('id');

        $this->assertSame('alt-2', ShopifyImageUtility::pickAlternate($alternates, 2)->id);
    }

    public function testResolveAltImageIdIsNullForStoresWithoutNumber()
    {
        $mainImage = new ProductImages(['id' => self::IMAGE_ID, 'main_image' => 1]);

        $this->assertNull(ShopifyImageUtility::resolveAltImageId($mainImage, new Store(['product_alt_image_number' => null])));
        $this->assertNull(ShopifyImageUtility::resolveAltImageId($mainImage, new Store(['product_alt_image_number' => 0])));
    }

    public function testOnlyTheMainImageIsSwapped()
    {
        $store = new Store(['product_alt_image_number' => 1]);

        // Returns before loading alternates, so no database is needed.
        $this->assertNull(ShopifyImageUtility::resolveAltImageId(new ProductImages(['id' => self::IMAGE_ID, 'main_image' => 0]), $store));
        $this->assertNull(ShopifyImageUtility::resolveAltImageId(new ProductImages(['id' => self::IMAGE_ID, 'main_image' => null]), $store));
    }

    public function testAltImageNumberIsNeverNegative()
    {
        $this->assertSame(0, (new Store(['product_alt_image_number' => -1]))->altImageNumber());
        $this->assertSame(0, (new Store(['product_alt_image_number' => null]))->altImageNumber());
        $this->assertSame(2, (new Store(['product_alt_image_number' => '2']))->altImageNumber());
    }
}
