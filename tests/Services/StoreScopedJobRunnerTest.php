<?php

namespace Tests\Services;

use App\Jobs\FetchEbayOrders;
use App\Jobs\FetchShopifyOrders;
use App\Jobs\FetchShopifyProductsJob;
use App\Jobs\Sync\OyderaMasterSwitch;
use App\Jobs\TransactionStatusCheckJob;
use App\Constants\JobRunMode;
use App\Jobs\Shopify\SyncShopifyDeviceSkuMetafieldsJob;
use App\Services\Jobs\StoreScopedJobRunner;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Read-only: uses the stores already in the configured database and never dispatches or writes.
 * Run by path: ./vendor/bin/phpunit tests/Services/StoreScopedJobRunnerTest.php
 */
class StoreScopedJobRunnerTest extends TestCase
{
    private StoreScopedJobRunner $runner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runner = app(StoreScopedJobRunner::class);
    }

    private function eligibleIds(string $class, int $take): array
    {
        $ids = $this->runner->eligibleStoresFor($class)->pluck('id')->take($take)->all();

        if (count($ids) < $take) {
            $this->markTestSkipped("Needs at least {$take} eligible stores for " . class_basename($class));
        }

        return $ids;
    }

    public function testDetectsStoreScopedJobs()
    {
        $this->assertTrue($this->runner->isStoreScoped(FetchShopifyOrders::class));
        $this->assertFalse($this->runner->isStoreScoped(TransactionStatusCheckJob::class));
        $this->assertFalse($this->runner->isStoreScoped(null));
        $this->assertFalse($this->runner->isStoreScoped('App\\Jobs\\DoesNotExist'));
    }

    public function testBuildWithoutStoresKeepsTheDefaultBehaviour()
    {
        $job = $this->runner->build(FetchShopifyOrders::class, null);

        $this->assertInstanceOf(FetchShopifyOrders::class, $job);
        $this->assertNull($job->storeIds);
    }

    public function testBuildKeepsOnlyTheStoresEligibleForTheJob()
    {
        $shopify = $this->eligibleIds(FetchShopifyOrders::class, 2);
        $ebay = $this->eligibleIds(FetchEbayOrders::class, 1);

        $job = $this->runner->build(FetchShopifyOrders::class, array_merge($shopify, $ebay));

        $this->assertEqualsCanonicalizing($shopify, $job->storeIds);
        $this->assertNotEmpty($job->store_name);
    }

    public function testBuildSkipsAJobNoneOfTheSelectedStoresApplyTo()
    {
        $ebay = $this->eligibleIds(FetchEbayOrders::class, 1);

        $this->assertNull($this->runner->build(FetchShopifyOrders::class, $ebay));
    }

    public function testBuildBuildsNonScopedJobsWithoutStores()
    {
        $ebay = $this->eligibleIds(FetchEbayOrders::class, 1);

        $this->assertInstanceOf(TransactionStatusCheckJob::class, $this->runner->build(TransactionStatusCheckJob::class, $ebay));
    }

    public function testSelectionReplacesOnlyTheFirstParameter()
    {
        $shopify = $this->eligibleIds(FetchShopifyProductsJob::class, 2);

        // parameters as scheduled_cron_jobs would hold them: [store_id, ignoreAutoGenerate]
        $job = $this->runner->build(FetchShopifyProductsJob::class, $shopify, [$shopify[0], true]);

        $this->assertEqualsCanonicalizing($shopify, $job->storeIds);
        $this->assertTrue($job->ignoreAutoGenerate);

        // no selection: parameters are spread exactly like routes/console.php
        $job = $this->runner->build(FetchShopifyProductsJob::class, null, [$shopify[0], true]);

        $this->assertSame([$shopify[0]], $job->storeIds);
        $this->assertTrue($job->ignoreAutoGenerate);
    }

    public function testAssertEligibleRejectsStoresOfAnotherType()
    {
        $ebay = $this->eligibleIds(FetchEbayOrders::class, 1);

        $this->expectException(ValidationException::class);
        $this->runner->assertEligible([FetchShopifyOrders::class], $ebay);
    }

    public function testAssertEligibleAcceptsStoresOfAnyJobInTheChain()
    {
        $shopify = $this->eligibleIds(FetchShopifyOrders::class, 1);
        $ebay = $this->eligibleIds(FetchEbayOrders::class, 1);

        $this->runner->assertEligible([FetchShopifyOrders::class, FetchEbayOrders::class], array_merge($shopify, $ebay));
        $this->addToAssertionCount(1);
    }

    public function testAssertEligibleRejectsJobsThatAreNotStoreScoped()
    {
        $ebay = $this->eligibleIds(FetchEbayOrders::class, 1);

        $this->expectException(ValidationException::class);
        $this->runner->assertEligible([TransactionStatusCheckJob::class], $ebay);
    }

    public function testStoreOptionsListsTheDefaultStores()
    {
        $options = $this->runner->storeOptions([OyderaMasterSwitch::class]);

        $this->assertTrue($options['scoped']);
        $this->assertCount(1, $options['jobs']);

        $defaultIds = collect($options['stores'])->where('is_default', true)->pluck('id')->all();
        $this->assertEqualsCanonicalizing(OyderaMasterSwitch::defaultStoreIds(), $defaultIds);
    }

    public function testStoreOptionsForNonScopedJobs()
    {
        $this->assertSame(['scoped' => false, 'jobs' => [], 'stores' => []], $this->runner->storeOptions([TransactionStatusCheckJob::class]));
    }

    public function testRunModeOfStoreScopedJob()
    {
        $this->assertSame(
            ['mode' => JobRunMode::STORE_PICKER, 'store_types' => ['shopify']],
            $this->runner->runMode([SyncShopifyDeviceSkuMetafieldsJob::class])
        );
    }

    public function testRunModeMergesTheStoreTypesOfAChain()
    {
        $runMode = $this->runner->runMode([FetchShopifyOrders::class, FetchEbayOrders::class, TransactionStatusCheckJob::class]);

        $this->assertSame(JobRunMode::STORE_PICKER, $runMode['mode']);
        $this->assertEqualsCanonicalizing(
            array_merge(FetchShopifyOrders::eligibleStoreTypes(), FetchEbayOrders::eligibleStoreTypes()),
            $runMode['store_types']
        );
    }

    public function testRunModeOfNonScopedJobsAndCommands()
    {
        $single = ['mode' => JobRunMode::SINGLE_RUN, 'store_types' => []];

        $this->assertSame($single, $this->runner->runMode([TransactionStatusCheckJob::class]));
        $this->assertSame($single, $this->runner->runMode([]));
        $this->assertSame($single, $this->runner->runMode(['App\\Jobs\\DoesNotExist']));
    }

    public function testFeedTriggerIsLimitedToConfiguredStoresButThePickerIsNot()
    {
        $storeId = $this->eligibleIds(SyncShopifyDeviceSkuMetafieldsJob::class, 1)[0];
        $allShopify = $this->runner->eligibleStoresFor(SyncShopifyDeviceSkuMetafieldsJob::class)->count();

        config(['services.shopify.device_sku_feed_store_ids' => [$storeId]]);

        $this->assertTrue(SyncShopifyDeviceSkuMetafieldsJob::shouldRunAfterFeed($storeId));
        $this->assertFalse(SyncShopifyDeviceSkuMetafieldsJob::shouldRunAfterFeed('not-a-store'));
        $this->assertFalse(SyncShopifyDeviceSkuMetafieldsJob::shouldRunAfterFeed(null));
        $this->assertSame($allShopify, $this->runner->eligibleStoresFor(SyncShopifyDeviceSkuMetafieldsJob::class)->count());
    }
}
