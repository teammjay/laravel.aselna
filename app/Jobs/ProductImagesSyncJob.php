<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ProductImages;
use App\Models\SysFile;
use App\Utils\FileUtility;
use App\Services\CriticalErrorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Jobs\Concerns\TrackableJob;

class ProductImagesSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 6000;

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
        $this->updateTrackingRecord(['tags' => 'Product Images']);
        Log::info('ProductImagesSyncJob: Start');

        try {
            //add local env variable to check if we are in local environment
//        if (env('APP_ENV') === 'local') return;
            $productImagesQ = DB::connection('fonus_db')
                ->table('tbl_product_images')
                ->where('picture_type_id', '=', '1')
                ->orderBy('product_id');

            if (env('APP_ENV') === 'local') {
                $productImagesQ->limit(40);
            }

            $productImagesQ->chunk(1000, function ($productImages) {
                foreach ($productImages as $productImage) {
                    Log::info('ProductImagesSyncJob: ' . $productImage->product_id);
                    $this->createProductImage($productImage);
                }
            });

            ProductImages::orderBy('id')->chunk(1000, function ($productImages) {
                Log::info('Processing chunk of ' . count($productImages) . ' product images for deletion check.');
                $this->checkAndDelete($productImages);
            });

            Log::info('ProductImagesSyncJob From : Done');

        }catch (\Throwable $e) {

            CriticalErrorService::report('Product Images Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
//
//        $easyImagesQ = DB::connection('easyesale_db')
//            ->table('product_image')
//            ->orderBy('id');
//
//        if (env('APP_ENV') === 'local') {
////            $easyImagesQ->limit(40);
//        }
//
//        $easyImagesQ->chunk(100, function ($productImages) {
//            foreach ($productImages as $productImage) {
//                $this->createProductImageFromEasyEsale($productImage);
//            }
//        });
    }

    private function createProductImage(mixed $productImage)
    {
        $product = Catalog::select('product_id')->where('product_code', $productImage->product_id)->first();
        if (!$product) return;
        $mainImageDetail = null;
        $thumbnailImageDetail = null;

        $SystemProductImage = ProductImages::select('updated_at')->where('product_id', $product->product_id)->where('sort_order', $productImage->picture_id)->first();

        Log::info('Product Image Date ' . $productImage->date_updated);
        // TODO Need to remove on 1 Jan 2026
        if (isset($SystemProductImage) && $SystemProductImage->updated_at >= ($productImage->date_updated ?? now())) {
            return;
        }

        //check if file exists in storage
        try {
            if(!isset($mainImageDetail)) {
                Log::info('ProductImagesSyncJob: ' . $productImage->picture_name);
                $imagePath = env('FONUS_BASE_URL') . 'inv_images/main_product_images/' . $productImage->picture_name;
                $mainImageDetail = FileUtility::saveImage($imagePath, 'product_images', $productImage->picture_name);
            }
        } catch (Exception $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            Log::error('ProductImagesSyncJob: ' . $e->getMessage());
        }

        try {
            if(!isset($thumbnailImageDetail)) {
                $imageThumbnail = env('FONUS_BASE_URL') . 'inv_images/main_product_images/' . $productImage->thumbnail_picture_name;
                $thumbnailImageDetail = FileUtility::saveImage($imageThumbnail, 'product_images', $productImage->thumbnail_picture_name);
            }
        } catch (Exception $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            Log::error('ProductImagesSyncJob: ' . $e->getMessage());
        }

//        if ($productImage->picture_id === 1) {
//            $product->update([
//                'product_image_url' => $thumbnailImageDetail ? $thumbnailImageDetail->id : $mainImageDetail->id
//            ]);
//        }


        try {
            ProductImages::updateOrCreate([
                'product_id' => $product->product_id,
                'sort_order' => $productImage->picture_id
            ], [
                'product_id' => $product->product_id,
                'main_image' => $productImage->picture_id === 1 ? true : false,
                'image' => $mainImageDetail ? $mainImageDetail->id : '',
                'thumbnail_image' => $thumbnailImageDetail ? $thumbnailImageDetail->id : null,
                'sort_order' => $productImage->picture_id,
                'code' => $productImage->product_id . '-' . $productImage->picture_id
            ]);
        } catch (Exception $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            Log::error('ProductImagesSyncJob: ' . $e->getMessage());
        }
    }

    private function createProductImageFromEasyEsale(mixed $productImage)
    {
//        Log::info('ProductImagesSyncJob From EasyEsale: ' . $productImage->id);

        $product = Catalog::where('product_id', $productImage->product_id)->first();
        if (!$product) return;

        $imageData = [
            'id' => $productImage->id,
            'product_id' => $product->product_id,
            'sort_order' => last(str_split($productImage->sort_order ?? '')),
            'main_image' => $productImage->main_image ? 1 : 0
        ];

        $Image = DB::connection('easyesale_db_wt_prefix')->table('sys_file')->where('id', $productImage->image_id)->first();

        try {
            $image_name = strtolower($Image->name);
            if (!ProductImages::where('image', '=', $image_name)->exists() && isset($image_name) && env('APP_ENV') !== 'local') {
                if (!file_exists(storage_path('app/public/product_images/' . $image_name))) {
                    $imagePath = env('FONUS_BASE_URL') . 'inv_images/main_product_images/' . $image_name;
                    $mainImageDetail = FileUtility::saveImage($imagePath, 'product_images', $image_name);
                    if (isset($mainImageDetail)) {
                        $imageData['image'] = $mainImageDetail->id;
                    }
                }
            }
            if (!isset($imageData['image'])) {
                $imageData['image'] = '';
            }
        } catch (Exception $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            $imageData['image'] = '';
            //Log::error('ProductImagesSyncJob From EasyEsale1: ' . $e->getMessage());
        }

        $Thumbnail = DB::connection('easyesale_db_wt_prefix')->table('sys_file')->where('id', $productImage->thumbnail_id)->first();
        try {
            $thunmail_name = strtolower($Thumbnail->name);
            if (!ProductImages::where('thumbnail_image', '=', $thunmail_name)->exists() && isset($thunmail_name) && env('APP_ENV') !== 'local') {
                if (!file_exists(storage_path('app/public/product_images/' . $thunmail_name))) {
                    $imageThumbnail = file_get_contents(env('FONUS_BASE_URL') . 'inv_images/main_product_images/' . $thunmail_name);
                    $thumbnailImageDetail = FileUtility::saveImage($imageThumbnail, 'product_images', $thunmail_name);
                    if (isset($thumbnailImageDetail)) {
                        $imageData['thumbnail_image'] = $thumbnailImageDetail->id;
                    }
                }
            }
            if (!isset($imageData['thumbnail_image'])) {
                $imageData['thumbnail_image'] = null;
            }
        } catch (Exception $e) {
            $imageData['thumbnail_image'] = null;
            //Log::error('ProductImagesSyncJob From EasyEsale2: ' . $e->getMessage());
        }

        if ($imageData['image'] != '' || $imageData['thumbnail_image'] != '') {
            ProductImages::updateOrCreate([
                'product_id' => $product->product_id,
                'sort_order' => last(str_split($productImage->sort_order ?? ''))
            ], $imageData);
        }
    }

    private function checkAndDelete($productImages)
    {
        $productImageKeys = [];
        $productImageMap = [];

        foreach ($productImages as $productImage) {
            $key = $productImage->product->product_code . '_' . $productImage->sort_order;
            $productImageKeys[] = $key;
            $productImageMap[$key] = $productImage->id;
        }

        Log::info('Checking existing product images for keys: ' . json_encode($productImageKeys));

        $existingRecords = DB::connection('fonus_db')->table('tbl_product_images')
            ->select(DB::raw("CONCAT(product_id, '_', picture_id) as `key`"))
            ->whereIn(DB::raw("CONCAT(product_id, '_', picture_id)"), array_unique($productImageKeys))
            ->where('picture_type_id', '=', '1')
            ->get();

        $existingSet = [];
        foreach ($existingRecords as $record) {
            $existingSet[$record->key] = true;
        }

        $idsToDelete = [];
        foreach ($productImageMap as $key => $id) {
            if (!isset($existingSet[$key])) {
                $idsToDelete[] = $id;
            }
        }
        Log::info('Product Images to delete :: ' . json_encode($idsToDelete));
        if (!empty($idsToDelete)) {
            ProductImages::whereIn('id', $idsToDelete)->delete();
        }
    }
}
