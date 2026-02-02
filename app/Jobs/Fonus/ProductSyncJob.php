<?php

namespace App\Jobs\Fonus;

use App\Constants\CatalogStatus;
use App\Jobs\Concerns\TrackableJob;
use App\Models\Catalog;
use App\Models\Manufacturer;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 5000;

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
        $this->updateTrackingRecord(['tags' => ' Fonus Products Sync job']);

        try {
            DB::connection('fonus_db')->table('tbl_product')
                ->orderBy('date_updated', 'desc')
                ->chunk(1000, function ($products) {
                    foreach ($products as $product) {
                        $this->syncFonusProduct($product);
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

    private function syncFonusProduct(mixed $product)
    {
        $alreadyExists = Catalog::where('product_code', $product->product_id)->first();
        if (isset($alreadyExists) && $alreadyExists->updated_at >= $product->date_updated) {
//            Log::info("Product Skipped :: " . $product->custom_label);
            return;
        }
        Log::info("Syncing Product :: " . $product->custom_label);

        $manufacturer = Manufacturer::where('code', $product->manufacturer_id)->first();

        Catalog::updateOrCreate(
            ['product_code' => $product->product_id],
            [
                'product_code' => $product->product_id,
                'product_title' => $product->product_name,
                'product_description' => $product->long_description,
//                'product_type' => $product->product_type_id,
                'manufacturer_id' => $manufacturer->id,
//                'product_condition_id' => $product->condition_id,
                'product_price' => $product->website_price,
                'product_wholesale_price' => $product->wholesale_price,
                'product_marketplace_price' => $product->marketplace_price,
                'product_mrsp' => $product->msrp,
                'product_sku' => $product->custom_label,
//                'parent_id' => $product->parent_id,
                //            'product_image_url' => 'as',
                'product_status' => CatalogStatus::getFonusStatus($product->status_id),
//                'dimensions_length' => $product->dimensions_length,
//                'dimensions_height' => $product->dimensions_height,
//                'dimensions_width' => $product->dimensions_width,
                'product_weight' => $product->weight,
//                'product_material_id' => $product->product_material_id,
//                'product_size_id' => $product->size_id,
//                'product_weight_unit' => $product->weight_weight_unit,
//                'product_package_weight' => $product->package_weight_value,
//                'package_weight_unit' => $product->package_weight_weight_unit,
//                'package_dimensions_width' => $product->package_dimension_width,
//                'package_dimensions_height' => $product->package_dimension_height,
//                'package_dimensions_length' => $product->package_dimension_length,
//                'shipping_package_id' => $product->shipping_package_id,
//                'oem_generic' => $product->oem_generic,
                //            'product_tags'=> $product->weight_weight_unit,
                //            'target_aud'=> $product->weight_weight_unit,
//                'product_color' => $product->color_id,
                'product_qty' => $product->total_stock,
                //            'product_reorder_qty',
                //            'product_pick_location' => ,
//                'title_template' => $product->title_template,
//                'description_template' => $product->description_template,
                'created_by' => 'system',
                'updated_by' => 'system',
            ]
        );
    }
}
