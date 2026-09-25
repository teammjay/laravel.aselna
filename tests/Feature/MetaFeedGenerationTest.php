<?php

namespace Tests\Feature;

use App\Jobs\ProcessFeedGenerationJob;
use App\Models\DeviceRating;
use App\Models\Feed;
use App\Models\FeedConfiguration;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The create-feed screen for a Meta feed: generate from the form, save a template, generate from
 * the template. The queue is faked, so no feed is built; the test checks the marketplace_feeds row
 * and what ProcessFeedGenerationJob is given. Rows it creates are removed again.
 */
class MetaFeedGenerationTest extends TestCase
{
    private const CONFIG_NAME = 'phpunit meta feed template';

    private array $feedIds = [];

    protected function tearDown(): void
    {
        Feed::whereIn('id', $this->feedIds)->delete();
        FeedConfiguration::where('name', self::CONFIG_NAME)->delete();
        parent::tearDown();
    }

    private function formData(Store $store, string $ratingId): array
    {
        // What #frmproductfilter posts: feed type radio, file type, product codes and rating filters.
        return [
            'store_id' => $store->id,
            'feed_type' => 'Meta',
            'file_type' => 'csv',
            'product_code' => ['C85'],
            'included_ratings' => [$ratingId],
        ];
    }

    private function dispatchedJobData(): array
    {
        $data = null;
        Queue::assertPushed(ProcessFeedGenerationJob::class, function ($job) use (&$data) {
            $data = (fn () => [$this->data, $this->feed_id])->call($job);

            return true;
        });
        $this->feedIds[] = $data[1];

        return $data;
    }

    public function testMetaFeedFromFormAndFromTemplate()
    {
        Queue::fake();
        $this->actingAs(User::first());
        $store = Store::where('name', 'NevaWireless')->whereNotNull('store_url')->firstOrFail();
        $rating10 = DeviceRating::where('value', 10)->value('id');

        // 1. Generate button
        $this->postJson(route('feed.generatefeed'), $this->formData($store, $rating10), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJson(['success' => true]);

        [$data, $feedId] = $this->dispatchedJobData();
        $feed = Feed::findOrFail($feedId);
        $this->assertSame('Meta', $feed->marketplace);
        $this->assertStringStartsWith('feeds/meta/meta_feed_', $feed->path);
        $this->assertSame('Meta', $data['feed_type']);
        $this->assertSame(['C85'], $data['product_code']);
        $this->assertSame([$rating10], $data['included_ratings']);

        // 2. Save configuration, then 3. Generate from it
        Queue::fake();
        $this->postJson(route('feed.saveconfiguration'), $this->formData($store, $rating10) + ['name' => self::CONFIG_NAME])
            ->assertOk()->assertJson(['success' => true]);
        $config = FeedConfiguration::where('name', self::CONFIG_NAME)->where('store_id', $store->id)->firstOrFail();
        $this->assertSame('Meta', $config->feed_type);
        $this->assertSame([$rating10], $config->filters['included_ratings']);

        $this->postJson(route('feed.generatefromconfiguration', $config->id))
            ->assertOk()->assertJson(['success' => true]);

        [$data, $feedId] = $this->dispatchedJobData();
        $this->assertStringStartsWith('feeds/meta/meta_feed_', Feed::findOrFail($feedId)->path);
        $this->assertSame('Meta', $data['feed_type']);
        $this->assertSame(['C85'], $data['product_code']);
        $this->assertSame([$rating10], $data['included_ratings']);
    }
}
