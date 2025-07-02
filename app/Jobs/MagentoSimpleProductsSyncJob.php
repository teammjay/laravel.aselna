<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\GenerateProductImage;
use App\Models\ManageMarketplaceProduct;
use App\Models\ProductImages;
use App\Models\ProductType;
use App\Models\Store;
use App\Services\Auth\MagentoAuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MagentoSimpleProductsSyncJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    public function handle(): void
    {
        $stores = Store::select('store.*')->with('fileSystem','storeType')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code','magento')->get();

        Log::info('Magento Simple Products Sync Job Started');
        if(count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                Log::info('Store : ' . $store->name);

                $pIds = ManageMarketplaceProduct::select('product_id')->where('upload_status','success')->get();

                $productTypes = ProductType::select('id')->where('code', 'ceabundle')->get()->toArray();

                $products = Catalog::select('*')->with('productType','productKeyFeatures','productColor')
                    ->whereNotIn('product_type', $productTypes)
                    ->whereNull('parent_id')
//                    ->where('product_status','!=','discontinued')
                    ->where('product_sku', 'E74')
                    ->whereNotIn('product_id', $pIds)
                    ->orderBy('created_at', 'desc')
                    ->chunk(100, function ($products) use ($store) {
                        foreach ($products as $product) {
                            $this->submitFeed($product,$store);
                        }
                    });

            }
        }

    }

    private function submitFeed(mixed $productData, mixed $store)
    {
        $fileSystem = $store->fileSystem;

        $is_uploaded = ManageMarketplaceProduct::where('product_id',$productData->product_id)->where('store_id',$store->id)->where('upload_status','success')->count();

        $product_id = $sku = $upcCode = NULL;

        if($is_uploaded > 0){
//            $product_id = $productData->product_id;
//
//            $proDetail = ManageMarketplaceProduct::where('product_id',$productData->product_id)->where('store_id',$store->id)->where('upload_status','success')->first();
//
//            $sku = $proDetail->sku;
//            $upcCode = $proDetail->upc_code;
//
//            $BulletPoints = '';
//            foreach ($productData->productKeyFeatures as $feature) {
//                $BulletPoints .= $feature->feature . ',';
//            }
//
//            $safeTextTitle = htmlspecialchars($productData->title, ENT_QUOTES | ENT_XML1, 'UTF-8');
//
////            $productReqData = [
////                'name' => $safeTextTitle,
////                'sku' => $proDetail->sku,
////                'price' => $proDetail->price,
////                'weight' => $proDetail->weight,
////                'attribute_set_id' => "4",
////                'custom_attributes' => [
////                    [
////                        'attribute_code' => 'meta_title',
////                        'value' => $listingItem->title
////                    ],
////                    [
////                        'attribute_code' => 'meta_keyword',
////                        'value' => $searchTermCombined,
////                    ],
////                    [
////                        'attribute_code' => 'meta_description',
////                        'value' => $listingItem->description
////                    ],
////                    [
////                        'attribute_code' => 'devices',
////                        'value' => $deviceOptionsID
////                    ],
////                    [
////                        'attribute_code' => 'brands',
////                        'value' => $brandsOptionsID
////                    ],
////                    [
////                        'attribute_code' => 'short_description',
////                        'value' => $listingItem->description
////                    ],
////                    [
////                        'attribute_code' => 'category_ids',
////                        'value' => $categoryIds
////                    ],
////                    [
////                        'attribute_code' => 'bullet_points',
////                        'value' => $BulletPoints
////                    ],
////                    [
////                        'attribute_code' => 'width',
////                        'value' => $productData->package_dimensions_width
////                    ],
////                    [
////                        'attribute_code' => 'height',
////                        'value' => $productData->package_dimensions_height
////                    ],
////                    [
////                        'attribute_code' => 'depth',
////                        'value' => $productData->package_dimensions_length
////                    ],
////                    [
////                        'attribute_code' => 'screen_length',
////                        'value' => $productData->screen_length
////                    ],
////                    [
////                        'attribute_code' => 'case_size',
////                        'value' => $productData->case_size
////                    ]
////                ],
////                'extension_attributes' => [
////                    'stock_item' => [
////                        'qty' => $itemDetail->qty,
////                        'is_in_stock' => true
////                    ]
////                ],
////                'media_gallery_entries' => $mediaGalleryEntries
////            ];
        } else {

//            $ManageMarketplaceProduct = ManageMarketplaceProduct::updateOrCreate([
//                'product_id' => $productData->product_id,
//                'product_code' => $productData->product_sku,
//                'store_id' => $store->id,
//            ], [
//                'product_id' => $productData->product_id,
//                'product_code' => $productData->product_sku,
//                'store_id' => $store->id,
//                'upload_status' => 'pending',
//                'upload_attempts' => 1,
//            ]);

            $descriptionHtml = '';
            if (isset($productData->productKeyFeatures)) {
                $descriptionHtml .= '<ul>';
                foreach ($productData->productKeyFeatures as $feature) {
                    $safeText = htmlspecialchars($feature->value, ENT_QUOTES | ENT_XML1, 'UTF-8'); // Escapes special characters
                    if (!empty(trim($safeText))) {
                        $descriptionHtml .= '<li>' . $safeText . '</li>';
                    }
                }

                $descriptionHtml .= '</ul>';
            }
            $descriptionHtml .= '<h2>Product Description</h2>';
            $descriptionHtml .= '<p>' . $productData->product_description . '</p>';

            $safeTextTitle = htmlspecialchars($productData->product_title, ENT_QUOTES | ENT_XML1, 'UTF-8');

            $searchTerms = $productData->searchTerms;
            //combine the search terms with spaces
            $searchTermCombined = implode(' ', $searchTerms->pluck('term')->toArray());

            $productReqData = [
                'name' => $safeTextTitle,
                'sku' => $productData->product_sku,
                'price' => $productData->product_price,
                'weight' => $productData->product_weight,
                'attribute_set_id' => "4",
                'custom_attributes' => [
                    [
                        'attribute_code' => 'meta_title',
                        'value' => $productData->product_title
                    ],
                    [
                        'attribute_code' => 'meta_keyword',
                        'value' => $searchTermCombined,
                    ],
                    [
                        'attribute_code' => 'meta_description',
                        'value' => $productData->product_description
                    ],
//                    [
//                        'attribute_code' => 'devices',
//                        'value' => $deviceOptionsID
//                    ],
//                    [
//                        'attribute_code' => 'brands',
//                        'value' => $brandsOptionsID
//                    ],
                    [
                        'attribute_code' => 'short_description',
                        'value' => $productData->description
                    ],
//                    [
//                        'attribute_code' => 'category_ids',
//                        'value' => $categoryIds
//                    ],
//                    [
//                        'attribute_code' => 'bullet_points',
//                        'value' => $BulletPoints
//                    ],
                    [
                        'attribute_code' => 'width',
                        'value' => $productData->package_dimensions_width
                    ],
                    [
                        'attribute_code' => 'height',
                        'value' => $productData->package_dimensions_height
                    ],
                    [
                        'attribute_code' => 'depth',
                        'value' => $productData->package_dimensions_length
                    ],
                    [
                        'attribute_code' => 'screen_length',
                        'value' => $productData->screen_length
                    ],
                    [
                        'attribute_code' => 'case_size',
                        'value' => $productData->case_size
                    ]
                ],
                'extension_attributes' => [
                    'stock_item' => [
                        'qty' => $productData->qty,
                        'is_in_stock' => true
                    ]
                ],
//                'media_gallery_entries' => $mediaGalleryEntries
            ];

            $magentoAuthService = new MagentoAuthService($store->seller_id, $store->secret_key);

            $response = $magentoAuthService->saveProduct($productReqData);
            Log::info('Product: ' . json_encode($response));

        }

    }

    public function generateProductImages($productData, $store){
        Log::info('Filesystem : ' . json_encode($store->fileSystem));
        ProductImages::where('product_id', $productData->product_id)
            ->chunk(100, function ($productImages) use ($store, $productData) {
                if (!isset($store->fileSystem)) return;
                foreach ($productImages as $productImage) {
                    $Image = $productImage->imageObj;
                    $path = '/store-images/' . $store->id . '/' . $productImage->id . '.jpg';

                    $imageData = [
                        'product_id' => $productData->product_id,
                        'product_image_id' => $productImage->id,
                        'alt_product_image_id' => null,
                        'image_file_system_id' => $Image->id,
                        'path' => $path,
                        'reference_id' => '',
                    ];

                    Log::info("Main Image :: " . json_encode($productImage));

                    if ($productImage->main_image == 1) {
                        $imageData['main_image'] = 1;
                    }
                    $this->uploadImage($store, $Image, $path);

                    GenerateProductImage::updateOrCreate([
                        'product_image_id' => $productImage->id,
                    ], $imageData);
                }
            });
    }
}
