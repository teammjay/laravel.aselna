<?php

namespace App\Jobs;

use App\Models\ProductColor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class ProductColorSync implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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
        $this->updateTrackingRecord(['tags' => 'Product Color']);
        DB::connection('easyesale_db')->table('product_color')
            ->orderBy('id')
            ->chunk(1000, function ($syncProductColors) {
                foreach ($syncProductColors as $productColor) {
                    $this->createSyncProductColor($productColor);
                }
            });
    }

    private function createSyncProductColor(mixed $productColor)
    {
        ProductColor::updateOrCreate([
            'id' => $productColor->id,
        ], [
            'id' => $productColor->id,
            'code' => $productColor->code,
            'name' => $productColor->name,
            'product_type_id' => $productColor->product_type_id,
            'client_id' => $productColor->client,
            'short_code' => $productColor->short_code,
            'channel_mapping_id' => $productColor->channel_mapping_id,
            'position_' => $productColor->position_,
        ]);
    }
}
