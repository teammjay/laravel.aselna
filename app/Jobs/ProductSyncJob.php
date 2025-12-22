<?php

namespace App\Jobs;

use App\Constants\CatalogStatus;
use App\Models\Catalog;
use App\Models\Manufacturer;
use App\Models\ProductCondition;
use App\Models\ProductKeyFeatures;
use App\Models\ProductMaterial;
use App\Models\ProductSize;
use App\Models\ProductTitleKeyword;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Services\CriticalErrorService;
use Exception;
use Google\Service\ShoppingContent\ProductStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Jobs\Concerns\TrackableJob;

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
        $this->updateTrackingRecord(['tags' => 'Products']);


        $producttypes = DB::connection('easyesale_db')->table('product_type')
            ->select('id')->where('code', 'ceabundle')->get()->toArray();
        Log::info('Product Types to Exclude :: ' . json_encode($producttypes));

        $easyesaleProductsQ = DB::connection('easyesale_db')->table('product')
            ->whereNull('deleted_by')
            ->whereNull('parent_id')
            ->whereNotIn('product_type_id', array_map(function ($item) {
                return $item->id;
            }, $producttypes))
            ->orderBy('create_ts', 'desc');

        if (env('APP_ENV') === 'local') {
            Log::info('Local Environment');
            $easyesaleProductsQ->where('oem_generic', '=', '2');
        }

        try {
            $easyesaleProductsQ->chunk(100, function ($easyesaleProducts) {
                foreach ($easyesaleProducts as $product) {
                    try {
                        $this->updateProduct($product);

                        $this->createProductTitleKeywords($product);

//                        $fonusProduct = DB::connection('fonus_db')->table('tbl_product')->where('custom_label', $product->code)->first();
//                        $this->createProductFeatures($fonusProduct, $product->id);
                    } catch (Exception $e) {

                        CriticalErrorService::report('Products Sync Job Failed', [
                            'marketplace' => 'N/A',
                            'reason' => $e->getMessage() ?? 'Unknown',
                            'method' => __METHOD__
                        ]);
                    }
                }
            });
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }

    private function createProduct($product)
    {
        $manufacturer = Manufacturer::where('code', $product->manufacturer_id)->first();
        $newProduct = Catalog::updateOrInsert(
            ['product_code' => $product->product_id],
            [
                'product_id' => (string)Str::uuid(),
                'product_title' => $product->product_name,
                'product_description' => $product->long_description,
                'product_sku' => $product->custom_label,
                'product_status' => $product->status_id == 1 ? 'active' : 'discontinued',
                'product_color' => $product->color,
                'product_code' => $product->product_id,
                'product_weight' => $product->weight,
                'product_price' => $product->website_price,
                'product_wholesale_price' => $product->wholesale_price,
                'product_marketplace_price' => $product->marketplace_price,
                'product_mrsp' => $product->msrp,
                'manufacturer_id' => $manufacturer->id,

                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function createProductTitleKeywords($product)
    {
        try {
            if (!Catalog::where('product_id', $product->id)->exists()) {
                return;
            }
            if (!DB::connection('easyesale_db')->table('product_title_keyword')->where('product_id', $product->id)) {
                return;
            }
            $keywords = DB::connection('easyesale_db')->table('product_title_keyword')->where('product_id', $product->id)->get();

            foreach ($keywords as $keyword) {
                ProductTitleKeyword::updateOrInsert(
                    ['id' => $keyword->id,'product_id' => $product->id, 'keyword' => $keyword->keyword],
                    [
                        'id' => $keyword->id,
                        'keyword' => $keyword->keyword,
                        'priority' => $keyword->priority,
                        'product_id' => $product->id,
                        'created_by' => 'system',
                        'updated_by' => 'system',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        } catch (Exception $e) {
            Log::info('Test Error ::' . $e->getMessage());
        }
    }

    private function createProductFeatures(mixed $product, $product_id)
    {
        try {
            if (!isset($product)) return;
            $bullet_points = [
                '1' => $product->bullet_point1,
                '2' => $product->bullet_point2,
                '3' => $product->bullet_point3,
                '4' => $product->bullet_point4,
                '5' => $product->bullet_point5,
                '6' => $product->bullet_point6,
            ];
            foreach ($bullet_points as $key => $value) {
                ProductKeyFeatures::updateOrInsert(
                    ['product_id' => $product_id, 'sort_order' => $key],
                    [
                        'id' => (string)Str::uuid(),
                        'product_id' => $product_id,
                        'value' => $value,
                        'sort_order' => $key,
                    ]
                );
            }
        } catch (Exception $e) {
            Log::info('Test Error ::' . $e->getMessage());
        }
    }

    private function updateProduct(mixed $product)
    {
        try {
            // if (Catalog::where('product_sku', $product->code)->exists()) {
            //     return;
            // }
            $alreadyExists = Catalog::where('product_id', $product->id)->first();
            if (isset($alreadyExists) && $alreadyExists->updated_at >= $product->update_ts) {
                Log::info("Product Skipped :: " . $product->code);
                return;
            }
            Log::info("Product Info :: " . json_encode($product));
            Catalog::updateOrInsert([
                'product_id' => $product->id,
            ], [
                'product_id' => $product->id,
                'product_title' => $product->title,
                'product_description' => $product->description,
                'product_type' => $product->product_type_id,
                'manufacturer_id' => $product->manufacturer_id,
                'product_condition_id' => $product->condition_id,
                'product_price' => $product->price,
                'product_wholesale_price' => $product->wholesale_price,
                'product_marketplace_price' => $product->marketplace_price,
                'product_mrsp' => $product->msrp,
                'product_sku' => $product->code,
                'product_code' => $product->reference_id1,
                'parent_id' => $product->parent_id,
                //            'product_image_url' => 'as',
                'product_status' => CatalogStatus::getStatusName($product->status),
                'dimensions_length' => $product->dimensions_length,
                'dimensions_height' => $product->dimensions_height,
                'dimensions_width' => $product->dimensions_width,
                'product_weight' => $product->weight_value,
                'product_material_id' => $product->product_material_id,
                'product_size_id' => $product->size_id,
                'product_weight_unit' => $product->weight_weight_unit,
                'product_package_weight' => $product->package_weight_value,
                'package_weight_unit' => $product->package_weight_weight_unit,
                'package_dimensions_width' => $product->package_dimension_width,
                'package_dimensions_height' => $product->package_dimension_height,
                'package_dimensions_length' => $product->package_dimension_length,
                'shipping_package_id' => $product->shipping_package_id,
                'oem_generic' => $product->oem_generic,
                //            'product_tags'=> $product->weight_weight_unit,
                //            'target_aud'=> $product->weight_weight_unit,
                'product_color' => $product->color_id,
                'product_qty' => $product->quantity,
                //            'product_reorder_qty',
                //            'product_pick_location' => ,
                'title_template' => $product->title_template,
                'description_template' => $product->description_template,
                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => $product->create_ts,
                'updated_at' => now(),
            ]);
        } catch (Exception $e) {
            Log::info('Test Error ::' . $e->getMessage());
        }
    }

    private function createStoreProduct(mixed $storeProduct)
    {
        if (Catalog::where('product_id', $storeProduct->product_id)->doesntExist()) {
            return;
        }
        StoreProduct::updateOrCreate([
            'id' => $storeProduct->id,
        ], [
            'id' => $storeProduct->id,
            'product_id' => $storeProduct->product_id,
            'store_id' => $storeProduct->store_id,
            'active' => $storeProduct->active ? 1 : 0,
            'brand_combo' => $storeProduct->brand_combo,
            'carrier_combo' => $storeProduct->carrier_combo,
            'store_type_category_id' => $storeProduct->channel_category_id,
            'client' => $storeProduct->client,
            'compare_at_price' => $storeProduct->compare_at_price,
            'description' => $storeProduct->description,
            'errors' => $storeProduct->errors,
            'fulfillment_by_channel_price' => $storeProduct->fulfillment_by_channel_price,
            'fulfillment_type' => $storeProduct->fulfillment_type,
            'include_carriers' => $storeProduct->include_carriers,
            'last_sale_date' => $storeProduct->last_sale_date,
            'last_synch_date_time' => $storeProduct->last_synch_date_time,
            'last_update_date_time' => $storeProduct->last_update_date_time,
            'listing_generation_type_id' => $storeProduct->listing_generation_type_id,
            'listing_title_template_id' => $storeProduct->listing_title_template_id,
//            'long_tail_keywords' => $storeProduct,
            'manufacturer_id' => $storeProduct->manufacturer_id,
            'msrp' => $storeProduct->msrp,
            'overwrite_compare_at_price' => $storeProduct->overwrite_compare_at_price,
            'overwrite_description' => $storeProduct->overwrite_description,
            'overwrite_fulfillment_by_channel_price' => $storeProduct->overwrite_fulfillment_by_channel_price,
            'overwrite_msrp' => $storeProduct->overwrite_msrp,
            'overwrite_price' => $storeProduct->overwrite_price,
            'overwrite_title' => $storeProduct->overwrite_title,
            'overwrite_wholesale_price' => $storeProduct->overwrite_wholesale_price,
            'preserve_title_description' => $storeProduct->preserve_title_description,
            'price' => $storeProduct->price,
            'profit_rate' => $storeProduct->profit_rate,
            'sales_count' => $storeProduct->sales_count,
            'short_tail_keywords' => $storeProduct->short_tail_keywords,
//            'store_category_id' => $storeProduct->store_category_id ,
            'synch_inventory' => $storeProduct->synch_inventory,
            'synch_item' => $storeProduct->synch_item,
            'synch_price' => $storeProduct->synch_price,
            'title' => $storeProduct->title,
//            'wholesale_price' => $storeProduct,

            'created_by' => 'system',
            'updated_by' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
