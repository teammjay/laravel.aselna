<?php

namespace Tests\Jobs;

use App\Jobs\Shopify\SyncShopifyDeviceSkuMetafieldsJob as Job;
use PHPUnit\Framework\TestCase;

/**
 * Covers the pure parts of the job (map building, encoding, response handling). No database or
 * Shopify calls.
 */
class SyncShopifyDeviceSkuMetafieldsJobTest extends TestCase
{
    private function row($listingItemId, $deviceCode, $sku): object
    {
        return (object)['listing_item_id' => $listingItemId, 'device_code' => $deviceCode, 'sku' => $sku];
    }

    public function testFirstRowPerDeviceWinsLikeTheFeed()
    {
        $maps = Job::buildDeviceSkuMaps([
            $this->row('li-1', '4074', 'A20-FIRST'),
            $this->row('li-1', '4074', 'A20-SECOND'),
            $this->row('li-1', '255', 'A20-OTHER'),
            $this->row('li-2', '4074', 'B10-AAAAAA'),
        ]);

        $this->assertSame(['255' => 'A20-OTHER', '4074' => 'A20-FIRST'], $maps['li-1']);
        $this->assertSame(['4074' => 'B10-AAAAAA'], $maps['li-2']);
    }

    public function testRowsWithoutSkuOrDeviceCodeAreSkipped()
    {
        $maps = Job::buildDeviceSkuMaps([
            $this->row('li-1', '1', ''),
            $this->row('li-1', '2', null),
            $this->row('li-1', null, 'A20-NODEV'),
            $this->row('li-1', '', 'A20-EMPTYCODE'),
            $this->row('li-1', '4', 'A20-KEEP'),
        ]);

        $this->assertSame(['4' => 'A20-KEEP'], $maps['li-1']);
    }

    public function testMapIsSortedSoTheJsonIsStable()
    {
        $a = Job::buildDeviceSkuMaps([$this->row('li', '10', 'X'), $this->row('li', '2', 'Y')]);
        $b = Job::buildDeviceSkuMaps([$this->row('li', '2', 'Y'), $this->row('li', '10', 'X')]);

        $this->assertSame(Job::encodeMap($a['li']), Job::encodeMap($b['li']));
        $this->assertSame('{"2":"Y","10":"X"}', Job::encodeMap($a['li']));
    }

    public function testEncodeIsAlwaysAnObject()
    {
        $this->assertSame('{}', Job::encodeMap([]));
        $this->assertSame('{"0":"A","1":"B"}', Job::encodeMap([0 => 'A', 1 => 'B']));
    }

    public function testProductGid()
    {
        $this->assertSame('gid://shopify/Product/123', Job::productGid('123'));
        $this->assertSame('gid://shopify/Product/123', Job::productGid('gid://shopify/Product/123'));
    }

    public function testNoResponseFailsWholeBatch()
    {
        $this->assertCount(3, Job::failedIndexes(null, 3));
        $this->assertCount(2, Job::failedIndexes(['errors' => [['message' => 'Throttled']]], 2));
        $this->assertCount(2, Job::failedIndexes(['data' => []], 2));
    }

    public function testUserErrorsFailOnlyTheirEntry()
    {
        $failed = Job::failedIndexes(['data' => ['metafieldsSet' => [
            'metafields' => [],
            'userErrors' => [['field' => ['metafields', '1', 'value'], 'message' => 'Value too long', 'code' => 'INVALID']],
        ]]], 3);

        $this->assertSame([1 => 'Value too long'], $failed);
    }

    public function testUserErrorWithoutIndexFailsWholeBatch()
    {
        $failed = Job::failedIndexes(['data' => ['metafieldsSet' => [
            'userErrors' => [['field' => null, 'message' => 'Access denied']],
        ]]], 2);

        $this->assertSame([0 => 'Access denied', 1 => 'Access denied'], $failed);
    }

    public function testSuccessHasNoFailures()
    {
        $this->assertSame([], Job::failedIndexes(['data' => ['metafieldsSet' => ['metafields' => [[]], 'userErrors' => []]]], 1));
    }
}
