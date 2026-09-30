<?php

namespace Tests\Jobs;

use App\Jobs\FetchShopifyOrders;
use App\Jobs\FetchShopifyProductsJob;
use App\Jobs\Sync\OyderaMasterSwitch;
use Tests\TestCase;

/**
 * Read-only: only builds job instances, never dispatches them.
 * Run by path: ./vendor/bin/phpunit tests/Jobs/ScopesToStoresTest.php
 */
class ScopesToStoresTest extends TestCase
{
    public function testNoStoresMeansDefaultBehaviour()
    {
        $this->assertNull((new FetchShopifyOrders())->storeIds);
        $this->assertNull((new FetchShopifyOrders(null))->storeIds);
    }

    public function testEmptyListIsTreatedAsNoStores()
    {
        // ScheduledjobsController::run used to call new $class([]); that must not mean "no stores"
        $this->assertNull((new FetchShopifyOrders([]))->storeIds);
        $this->assertNull((new FetchShopifyOrders(''))->storeIds);
    }

    public function testSingleStoreIdBecomesAList()
    {
        $job = new FetchShopifyProductsJob('store-1', true);

        $this->assertSame(['store-1'], $job->storeIds);
        $this->assertTrue($job->ignoreAutoGenerate);
    }

    public function testListIsDeduplicated()
    {
        $this->assertSame(['a', 'b'], (new OyderaMasterSwitch(['a', 'b', 'a', '']))->storeIds);
    }

    public function testStoreIdsSurviveQueueSerialization()
    {
        $job = unserialize(serialize(new FetchShopifyOrders(['a', 'b'])));

        $this->assertSame(['a', 'b'], $job->storeIds);
    }

    public function testExistingListingJobsShareTheirSiblingsMapping()
    {
        $this->assertSame(\App\Jobs\AmazonListingItemSyncJob::class, \App\Jobs\Amazon\ExistingListingItemSyncJob::storeMappingClass());
        $this->assertSame(\App\Jobs\EbayListingItemSyncJob::class, \App\Jobs\Ebay\ExistingListingItemSyncJob::storeMappingClass());
        $this->assertSame(\App\Jobs\WalmartListingItemSyncJob::class, \App\Jobs\Walmart\ExistingListingItemSyncJob::storeMappingClass());
        $this->assertSame(FetchShopifyOrders::class, FetchShopifyOrders::storeMappingClass());
    }
}
