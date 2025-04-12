<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ProductTags;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProductTagSyncJob implements ShouldQueue
{
    use Queueable;

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
        $productTypeKeywordQ = DB::connection('easyesale_db')->table('product_tag')
            ->orderBy('id');

        $productTypeKeywordQ->chunk(1000, function ($productTags) {
            foreach ($productTags as $productTag) {
                $this->createProductTag($productTag);
            }
        });
    }

    private function createProductTag(mixed $productTag)
    {
        if (Catalog::where('product_id', $productTag->product_id)->doesntExist()) {
            return;
        }
        ProductTags::updateOrCreate([
            'id' => $productTag->id
        ], [
            'id' => $productTag->id,
            'product_id' => $productTag->product_id,
            'name' => $productTag->name,
        ]);
    }
}
