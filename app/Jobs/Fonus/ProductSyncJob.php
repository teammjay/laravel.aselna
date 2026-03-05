<?php

namespace App\Jobs\Fonus;

use App\Constants\CatalogStatus;
use App\Jobs\Concerns\TrackableJob;
use App\Models\Catalog;
use App\Models\Manufacturer;
use App\Models\ProductColor;
use App\Models\ProductCondition;
use App\Models\ProductKeyFeatures;
use App\Models\ProductType;
use App\Services\CriticalErrorService;
use App\Utils\FileUtility;
use Exception;
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
    private $existingProductsMap;

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
        // Mark as processing when job starts
        $this->updateTrackingRecord([
            'status' => 'processing',
            'started_at' => now(),
            'tags' => ' Fonus Products Sync job'
        ]);

        try {
            $this->existingProductsMap = Catalog::pluck('updated_at', 'product_code')->toArray();

            DB::connection('fonus_db')->table('tbl_product')
                ->orderBy('product_id')
                ->chunk(1000, function ($products) {
                    foreach ($products as $product) {
                        $this->syncFonusProduct($product);
                    }
                });

            // Mark as completed when job finishes successfully
            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {
            // Mark as failed when job fails
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Fonus Products Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            throw $e; // Re-throw to trigger chain failure handling
        }
    }

    private function syncFonusProduct(mixed $product)
    {
        if (isset($this->existingProductsMap[$product->product_id])) {
            $localUpdatedAt = $this->existingProductsMap[$product->product_id];

            // Debugging: If this log appears too often, you know your dates aren't matching up
            // Log::info("Comparing Local: $localUpdatedAt vs Remote: {$product->date_updated}");

            if (strtotime($localUpdatedAt) >= strtotime($product->date_updated)) {
                return; // SKIPS INSTANTLY
            }
        }
        Log::info("Syncing Product :: " . $product->custom_label);
        Log::info('Product status id :: ' . $product->status_id);
        $alreadyExists = Catalog::select('updated_at','product_id','product_sku')->where('product_code', $product->product_id)->first();


        $manufacturer = Manufacturer::select('id')->where('code', $product->manufacturer_id)->first();
        $productColor = ProductColor::select('id')->where('name', $product->color)->first();
        $productCategory = ProductType::select('id')
            ->where('code', $product->category_id)
            ->whereIn('parent_id', function ($subquery) {
                $subquery->select('id')->from('product_type')->where('code', 'cea');
            })
            ->first();
        $productCondition = ProductCondition::select('id')->where('code', $product->stock_type_id)->first();
        $mainImage = null;

        try {
            if(!isset($mainImage)) {
                $imageThumbnail = env('FONUS_BASE_URL') . 'inv_images/main_product_images/' . $product->picture1;
                $mainImage = FileUtility::saveImage($imageThumbnail, 'product_images', $product->picture1);
            }
        } catch (Exception $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            Log::error('ProductImagesSyncJob: ' . $e->getMessage());
        }

        $addUpdateProduct = Catalog::updateOrCreate(
            ['product_code' => $product->product_id],
            [
                'product_code' => $product->product_id,
                'product_title' => $product->product_name,
                'product_description' => $product->long_description,
                'product_type' => $productCategory->id,
                'manufacturer_id' => $manufacturer->id,
                'product_condition_id' => $productCondition->id,
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
                'oem_generic' => $product->oem_gen_id,
                //            'product_tags'=> $product->weight_weight_unit,
                //            'target_aud'=> $product->weight_weight_unit,
                'product_color' => $productColor->id,
                'product_qty' => $product->total_stock,
                //            'product_reorder_qty',
                //            'product_pick_location' => ,
//                'title_template' => $product->title_template,
//                'description_template' => $product->description_template,
                'product_image_url' => $mainImage ? $mainImage->id : null,
                'created_by' => 'system',
                'updated_by' => 'system',
            ]
        );

        $this->syncProductKeyFeatures($product, $addUpdateProduct);
    }

    private function syncProductKeyFeatures($product,$aselnaProduct)
    {
        try {
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
