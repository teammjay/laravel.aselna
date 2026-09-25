<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ProductImageAlternative;
use App\Models\ProductImages;
use App\Models\SysFile;
use App\Utils\FileUtility;
use App\Services\CriticalErrorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Jobs\Concerns\TrackableJob;

class ProductAlternativeImagesSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 30000;

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
//        $productsImagesQ = DB::connection('easyesale_db')->table('product_image_alternative')
//            ->orderBy('id')
//            ->chunk(1000, function ($productsImages) {
//                foreach ($productsImages as $productImage) {
//                    $this->createProductImage($productImage);
//                }
//            });

        // Mark as processing when job starts
        $this->updateTrackingRecord([
            'status' => 'processing',
            'started_at' => now(),
            'tags' => 'Product Alternative Images'
        ]);

        try {
            $productImagesQ = DB::connection('fonus_db')
                ->table('tbl_product_main_images')
                ->orderBy('product_id');

            $productImagesQ->chunk(1000, function ($productsImages) {
                foreach ($productsImages as $productImage) {
                    $this->createProductImageFonus($productImage);
                }
            });

            // chunkById: rows are deleted while chunking, which makes offset chunks skip rows.
            ProductImageAlternative::chunkById(1000, function ($productImages) {
                $this->checkAndDeleteImage($productImages);
            });

            // Mark as completed when job finishes successfully
            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {
            // Mark as failed when job fails
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Product Alternative Images Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            throw $e; // Re-throw to trigger chain failure handling
        }
    }

    /**
     * Copy one Fonus alternate (tbl_product_main_images row) into product_image_alternatives.
     *
     * The row is only written when Fonus changed it (date_updated newer than our updated_at), the
     * same check ProductImagesSyncJob makes for product images. SubmitShopifyProductsJob re-pushes
     * a Shopify main image when its alternate's updated_at moves, so an unchanged alternate must
     * keep its updated_at. The files are downloaded again only then, into the sys_file rows the
     * alternate already has, instead of adding a new sys_file (and a new image id) on every run.
     */
    private function createProductImageFonus(mixed $productImage)
    {
        $product = Catalog::select('product_id')->where('product_code', $productImage->product_id)->first();
        if (!$product) return;

        $ourMainImage = ProductImages::select('id')->where('product_id',$product->product_id)->where('main_image',1)->first();
        $mainImageId = $ourMainImage->id ?? null;

        $alternate = ProductImageAlternative::where('product_id', $product->product_id)
            ->where('version_number', $productImage->image_id)
            ->first();

        $fonusChanged = !isset($alternate) || empty($alternate->image)
            || $alternate->updated_at < ($productImage->date_updated ?? '1970-01-01 00:00:00');

        if (!$fonusChanged) {
            // Keep the link to the main image right without marking the alternate as changed.
            if (isset($mainImageId) && $alternate->main_image_id != $mainImageId) {
                $alternate->timestamps = false;
                $alternate->main_image_id = $mainImageId;
                $alternate->save();
            }
            return;
        }

        $imageData = [
            'product_id' => $product->product_id,
            'version_number' => $productImage->image_id,
            'main_image_id' => $mainImageId
        ];

        $imagePath = env('FONUS_BASE_URL') . 'inv_images/additional_main_images/';
        $imageData['image'] = $this->saveAlternateFile($imagePath . $productImage->picture_name, $productImage->picture_name, $alternate->image ?? null);
        $imageData['thumbnail_image'] = $this->saveAlternateFile($imagePath . $productImage->tn_picture_name, $productImage->tn_picture_name, $alternate->thumbnail_image ?? null);

        // Download failed: leave the alternate as it is, so the next run tries again.
        if (!isset($imageData['image']) && isset($alternate)) {
            return;
        }

        $alternate = ProductImageAlternative::updateOrCreate([
            'product_id' => $product->product_id,
            'version_number' => $productImage->image_id
        ], array_filter($imageData));
        // The file may have changed under the same sys_file id, so nothing else would move updated_at.
        if (isset($imageData['image'])) {
            $alternate->touch();
        }
    }

    /**
     * Download a Fonus alternate file into storage/app/public/product_additional_images and
     * return its sys_file id. The alternate's current sys_file row is reused when it is the same
     * file name; a new one is only created for a new file. Returns null when the download fails,
     * so the alternate keeps the file it has.
     */
    private function saveAlternateFile(string $url, ?string $fileName, ?string $currentFileId): ?string
    {
        if (empty($fileName)) {
            return null;
        }

        try {
            $sysFile = isset($currentFileId) ? SysFile::where('id', $currentFileId)->where('name', $fileName)->first() : null;

            if (!isset($sysFile)) {
                return FileUtility::saveImage($url, 'product_additional_images', $fileName)?->id;
            }

            Log::info('ProductAlternativeImagesSyncJob: refreshing ' . $fileName);
            $content = file_get_contents($url);
            if ($content === false) {
                return null;
            }

            $storagePath = storage_path('app/public/product_additional_images/' . $fileName);
            File::ensureDirectoryExists(dirname($storagePath));
            file_put_contents($storagePath, $content);

            $sysFile->update([
                'file_size' => filesize($storagePath) ?: $sysFile->file_size,
                'location' => 'product_additional_images/',
            ]);

            return $sysFile->id;
        } catch (\Throwable $e) {
            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            Log::error('ProductAlternativeImagesSyncJob: ' . $e->getMessage());
        }

        return null;
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
                    $imagePath = env('FONUS_BASE_URL') . 'inv_images/additional_main_images/' . $image_name;
                    $mainImageDetail = FileUtility::saveImage($imagePath, 'product_additional_images/', $image_name);
                    $imageData['image'] = $mainImageDetail->id;
                }
            }
            if (!isset($imageData['image'])) {
                $imageData['image'] = '';
            }
        } catch (Exception $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

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
                    $imagePath = env('FONUS_BASE_URL') . 'inv_images/additional_main_images/' . $thunmail_name;
                    $mainImageDetail = FileUtility::saveImage($imagePath, 'product_additional_images/', $thunmail_name);

                    $imageData['thumbnail_image'] = $mainImageDetail->id;
                }
            }
            if (!isset($imageData['thumbnail_image'])) {
                $imageData['thumbnail_image'] = '';
            }
        } catch (Exception $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

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

    private function checkAndDeleteImage(mixed $productImages)
    {
        $productImageKeys = [];
        $productImageMap = [];
        foreach ($productImages as $image) {
            $key = $image->product->product_code . '_' . $image->version_number;
            $productImageKeys[] = $key;
            $productImageMap[$key] = $image->id;
        }

        Log::info('Checking Product Alternate Images to delete :: ' . json_encode($productImageKeys));

        $existingRecords = DB::connection('fonus_db')->table('tbl_product_main_images')
            ->select(DB::raw("CONCAT(product_id, '_', image_id) as `key`"))
            ->whereIn(DB::raw("CONCAT(product_id, '_', image_id)"), $productImageKeys)
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

        Log::info('Product Alternate Images to delete :: ' . json_encode($idsToDelete));
        if (!empty($idsToDelete)) {
            ProductImageAlternative::whereIn('id', $idsToDelete)->delete();
        }
    }


}
