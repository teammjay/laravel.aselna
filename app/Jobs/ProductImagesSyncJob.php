<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ProductImages;
use App\Models\SysFile;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductImagesSyncJob implements ShouldQueue
{
    use Queueable;

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
        //add local env variable to check if we are in local environment
//        if (env('APP_ENV') === 'local') return;
        $productImagesQ = DB::connection('fonus_db')
            ->table('tbl_product_images')
            ->where('picture_type_id', '=', '1')
            ->orderBy('product_id');

        if (env('APP_ENV') === 'local') {
            $productImagesQ->limit(40);
        }

        $productImagesQ->chunk(100, function ($productImages) {
            foreach ($productImages as $productImage) {
                $this->createProductImage($productImage);
            }
        });

        Log::info('ProductImagesSyncJob From : Done');

        $easyImagesQ = DB::connection('easyesale_db')
            ->table('product_image')
            ->orderBy('id');

        if (env('APP_ENV') === 'local') {
            $easyImagesQ->limit(40);
        }

        $easyImagesQ->chunk(100, function ($productImages) {
            foreach ($productImages as $productImage) {
                $this->createProductImageFromEasyEsale($productImage);
            }
        });
    }

    private function createProductImage(mixed $productImage)
    {
        $product = Catalog::where('product_code', $productImage->product_id)->first();
        if (!$product) return;
        $mainImageDetail = null;
        $thumbnailImageDetail = null;

        if (env('APP_ENV') !== 'local') {
            //check if file exists in storage
            try {
                if (!file_exists(storage_path('app/public/product_images/' . $productImage->picture_name))) {
                    Log::info('ProductImagesSyncJob: ' . $productImage->picture_name);
                    $imagePath = 'https://fonusaccessory.com/inv/inv_images/main_product_images/' . $productImage->picture_name;
                    $localPath = 'public/product_images/';
                    $storagePath = storage_path('app/' . $localPath . $productImage->picture_name);

                    $image = file_get_contents($imagePath);
                    if ($image !== false) {
                        Log::info('ProductImagesSyncJob: ' . $imagePath);
                        file_put_contents($storagePath, $image);

                        $extension = pathinfo($imagePath, PATHINFO_EXTENSION);

                        $fileSize = filesize($storagePath);

                        $publicUrl = Storage::url(str_replace('public/', '', $localPath . $productImage->picture_name));

                        $filedata = [
                            'name' => $productImage->picture_name,
                            'ext' => $extension ?: 'jpg', // Default to 'jpg' if missing
                            'file_size' => $fileSize ?: 'Unknown', // Use 'Unknown' if not determined
                            'location' => 'app/' . $localPath,
                            'created_by' => 'admin',
                        ];
                        Log::info('ProductImagesSyncJob: ' . json_encode($filedata));

                        $mainImageDetail = SysFile::create($filedata);

                    }
                } else  {
                    $mainImageDetail = SysFile::where('name', $productImage->picture_name)->first();
                }
            } catch (Exception $e) {
                Log::error('ProductImagesSyncJob: ' . $e->getMessage());
            }

            try {
                if (!file_exists(storage_path('app/public/product_images/' . $productImage->thumbnail_picture_name))) {
                    $imageThumbnail = file_get_contents('https://fonusaccessory.com/inv/inv_images/main_product_images/' . $productImage->thumbnail_picture_name);
                    $localPath = 'public/product_images/';
                    $storagePath = storage_path('app/' . $localPath . $productImage->thumbnail_picture_name);

                    if ($imageThumbnail !== false) {
                        file_put_contents($storagePath, $imageThumbnail);

                        $extension = pathinfo($productImage->thumbnail_picture_name, PATHINFO_EXTENSION);

                        $fileSize = filesize($storagePath);

                        $publicUrl = Storage::url(str_replace('public/', '', $localPath . $productImage->thumbnail_picture_name));

                        $filedata = [
                            'name' => $productImage->thumbnail_picture_name,
                            'ext' => $extension ?: 'jpg', // Default to 'jpg' if missing
                            'file_size' => $fileSize ?: 'Unknown', // Use 'Unknown' if not determined
                            'location' => 'app/' . $localPath,
                            'created_by' => 'admin',
                        ];

                        $thumbnailImageDetail = SysFile::create($filedata);

                        Log::info('ProductImagesSyncJob: ' . json_encode($filedata));
                    }
                } else {
                    $thumbnailImageDetail = SysFile::where('name', $productImage->thumbnail_picture_name)->first();
                }
            } catch (Exception $e) {
                Log::error('ProductImagesSyncJob: ' . $e->getMessage());
            }
        }

        if ($productImage->picture_id === 1) {
            $product->update([
                'product_image_url' => $productImage->picture_name,
            ]);
        }

        ProductImages::updateOrCreate([
            'product_id' => $product->product_id,
            'sort_order' => $productImage->picture_id
        ], [
            'product_id' => $product->product_id,
            'image' => $mainImageDetail ? $mainImageDetail->id : '',
            'thumbnail_image' => $thumbnailImageDetail ? $thumbnailImageDetail->id : null,
            'sort_order' => $productImage->picture_id
        ]);
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
                    $imagePath = 'https://fonusaccessory.com/inv/inv_images/main_product_images/' . $image_name;
                    $localPath = 'public/product_images/';
                    $storagePath = storage_path('app/' . $localPath . $image_name);

                    $image = file_get_contents($imagePath);
                    if ($image !== false) {
                        file_put_contents($storagePath, $image);

                        $extension = pathinfo($imagePath, PATHINFO_EXTENSION);

                        $fileSize = filesize($storagePath);

                        $publicUrl = Storage::url(str_replace('public/', '', $localPath . $image_name));

                        $filedata = [
                            'name' => $image_name,
                            'ext' => $extension ?: 'jpg', // Default to 'jpg' if missing
                            'file_size' => $fileSize ?: 'Unknown', // Use 'Unknown' if not determined
                            'location' => 'app/' . $localPath,
                            'created_by' => 'admin',
                        ];

                        $mainImageDetail = SysFile::create($filedata);
                        $imageData['image'] = $mainImageDetail->id;

                        Log::info('ProductImagesSyncJob: ' . json_encode($filedata));
                    }
                }
            }
            if (!isset($imageData['image'])) {
                $imageData['image'] = '';
            }
        } catch (Exception $e) {
            $imageData['image'] = '';
            //Log::error('ProductImagesSyncJob From EasyEsale1: ' . $e->getMessage());
        }

        $Thumbnail = DB::connection('easyesale_db_wt_prefix')->table('sys_file')->where('id', $productImage->thumbnail_id)->first();
        try {
            $thunmail_name = strtolower($Thumbnail->name);
            if (!ProductImages::where('thumbnail_image', '=', $thunmail_name)->exists() && isset($thunmail_name) && env('APP_ENV') !== 'local') {
                if (!file_exists(storage_path('app/public/product_images/' . $thunmail_name))) {
                    $imageThumbnail = file_get_contents('https://fonusaccessory.com/inv/inv_images/main_product_images/' . $thunmail_name);
                    $localPath = 'public/product_images/';
                    $storagePath = storage_path('app/' . $localPath . $thunmail_name);

                    if ($imageThumbnail !== false) {
                        file_put_contents($storagePath, $imageThumbnail);

                        $extension = pathinfo($productImage->thumbnail_picture_name, PATHINFO_EXTENSION);

                        $fileSize = filesize($storagePath);

                        $publicUrl = Storage::url(str_replace('public/', '', $localPath . $thunmail_name));

                        $filedata = [
                            'name' => $thunmail_name,
                            'ext' => $extension ?: 'jpg', // Default to 'jpg' if missing
                            'file_size' => $fileSize ?: 'Unknown', // Use 'Unknown' if not determined
                            'location' => 'app/' . $localPath,
                            'created_by' => 'admin',
                        ];

                        $thumbnailImageDetail = SysFile::create($filedata);

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
}
