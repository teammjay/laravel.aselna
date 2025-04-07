<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ProductImageAlternative;
use App\Models\ProductImages;
use App\Models\SysFile;
use App\Utils\FileUtility;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductAlternativeImagesSyncJob implements ShouldQueue
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
        $productsImagesQ = DB::connection('easyesale_db')->table('product_image_alternative')
            ->orderBy('id')
            ->chunk(1000, function ($productsImages) {
                foreach ($productsImages as $productImage) {
                    $this->createProductImage($productImage);
                }
            });
    }

    private function createProductImage(mixed $productImage)
    {
        Log::info('ProductAlternativeImagesSyncJob From : Started');
        Log::info('ProductAlternativeImagesSyncJob From Main ID :: ' . $productImage->main_image_id);

        $mainImage = DB::connection('easyesale_db')->table('product_image')->where('id', $productImage->main_image_id)->first();
//        Log::info('ProductAlternativeImagesSyncJob From Main Image :: ' . json_encode($mainImage));

        $product = Catalog::where('product_id', $mainImage->product_id)->first();
        if (!$product) return;

        Log::info("Main Image Id " . $productImage->main_image_id);

        $ourMainImage = ProductImages::where('product_id',$mainImage->product_id)->where('main_image',1)->first();

        $imageData = [
            'product_id' => $product->product_id,
            'version_number' => $productImage->version_number,
            'main_image_id' => $ourMainImage->id ?? null
        ];

        $Image = DB::connection('easyesale_db_wt_prefix')
            ->table('sys_file')
            ->where('id', $productImage->image_id)->first();
        try {
            Log::info('ProductAlternativeImagesSyncJob From EasyEsale: ' . json_encode($Image));
            $image_name = strtolower($Image->name);
            if (!ProductImageAlternative::where('image', '=', $image_name)->exists() && isset($image_name) && env('APP_ENV') !== 'local') {
                if (!file_exists(storage_path('app/public/product_additional_images/' . $image_name))) {
                    $imagePath = 'https://fonusaccessory.com/inv/inv_images/additional_main_images/' . $image_name;
                    $mainImageDetail = FileUtility::saveImage($imagePath, 'product_additional_images/', $image_name);
                    $imageData['image'] = $mainImageDetail->id;
                }
            }
            if (!isset($imageData['image'])) {
                $imageData['image'] = '';
            }
        } catch (Exception $e) {
            $imageData['image'] = '';
            Log::error('ProductAlternateImagesSyncJob From EasyEsale: ' . $e->getMessage());
        }

        $Thumbnail = DB::connection('easyesale_db_wt_prefix')
            ->table('sys_file')
            ->where('id', $productImage->thumbnail_id)->first();
        try {
            $thunmail_name = strtolower($Thumbnail->name);
            if (!ProductImageAlternative::where('thumbnail_image', '=', $thunmail_name)->exists() && isset($thunmail_name) && env('APP_ENV') !== 'local') {
                if (!file_exists(storage_path('app/public/product_additional_images/' . $thunmail_name))) {
                    $imagePath = 'https://fonusaccessory.com/inv/inv_images/additional_main_images/' . $thunmail_name;
                    $mainImageDetail = FileUtility::saveImage($imagePath, 'product_additional_images/', $thunmail_name);

                    $imageData['thumbnail_image'] = $mainImageDetail->id;
                }
            }
            if (!isset($imageData['thumbnail_image'])) {
                $imageData['thumbnail_image'] = '';
            }
        } catch (Exception $e) {
            $imageData['thumbnail_image'] = '';
            Log::error('ProductAlternateImagesSyncJob From EasyEsale: ' . $e->getMessage());
        }


        Log::info('Updating Image'. json_encode(array_filter($imageData)));

//        if ($imageData['image'] != '' || $imageData['thumbnail_image'] != '') {
            ProductImageAlternative::updateOrCreate([
                'product_id' => $product->product_id,
                'sort_order' => last(str_split($productImage->sort_order ?? ''))
            ], array_filter($imageData));
//        }

    }
}
