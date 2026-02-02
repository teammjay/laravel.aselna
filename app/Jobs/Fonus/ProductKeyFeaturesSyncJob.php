<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Catalog;
use App\Models\ProductKeyFeatures;
use App\Services\CriticalErrorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductKeyFeaturesSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 60000;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord(['tags' => ' Fonus Products Key features Sync job']);

        try {
            DB::connection('fonus_db')->table('tbl_product')
                ->orderBy('date_updated', 'desc')
                ->chunk(1000, function ($products) {
                    foreach ($products as $product) {
                        $this->syncProductKeyFeatures($product);
                    }
                });

        } catch (\Throwable $e) {

            CriticalErrorService::report('Fonus Products Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function syncProductKeyFeatures(mixed $product)
    {
        try {
            $aselnaProduct = Catalog::where('product_code', $product->product_id)->first();
            if (isset($aselnaProduct) && $aselnaProduct->updated_at >= $product->date_updated) {
//            Log::info("Product Skipped :: " . $product->custom_label);
                return;
            }
            $product_id = $aselnaProduct->product_id;
            Log::info('Syncing Key Features for Product Sku: ' . $aselnaProduct->product_sku . ' Product ID: ' . $product_id);
            if (!isset($product)) return;
            if (!isset($aselnaProduct)) return;
            $bullet_points = [
                '1' => $product->bullet_point1,
                '2' => $product->bullet_point2,
                '3' => $product->bullet_point3,
                '4' => $product->bullet_point4,
                '5' => $product->bullet_point5,
                '6' => $product->bullet_point6,
            ];
            foreach ($bullet_points as $key => $value) {
                ProductKeyFeatures::updateOrCreate(
                    ['product_id' => $product_id, 'sort_order' => $key],
                    [
                        'product_id' => $product_id,
                        'value' => $value,
                        'sort_order' => $key,
                    ]
                );
            }
        } catch (Exception $e) {
            Log::info('Key Features Error ::' . $e->getMessage());
        }
    }
}
