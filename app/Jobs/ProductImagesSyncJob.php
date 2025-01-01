<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ProductImages;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductImagesSyncJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3000;

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
        if (env('APP_ENV') === 'local') return;
        $productImages = DB::connection('fonus_db')->table('tbl_product_images')->cursor();

        foreach ($productImages as $productImage) {
            $this->createProductImage($productImage);
        }
    }

    private function createProductImage(mixed $productImage)
    {
        $product = Catalog::where('product_code', $productImage->product_id)->first();
        if (!$product) return;

        //check if file exists in storage
        if (!file_exists(storage_path('app/public/product_images/' . $productImage->picture_name))) {
            $image = file_get_contents('https://fonusaccessory.com/inv/inv_images/main_product_images/' . $productImage->picture_name);
            file_put_contents(storage_path('app/public/product_images/' . $productImage->picture_name), $image);
        }

        if (!file_exists(storage_path('app/public/product_images/' . $productImage->thumbnail_picture_name))) {
            $imageThumbnail = file_get_contents('https://fonusaccessory.com/inv/inv_images/main_product_images/' . $productImage->thumbnail_picture_name);
            file_put_contents(storage_path('app/public/product_images/' . $productImage->thumbnail_picture_name), $imageThumbnail);
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
            'image' => $productImage->picture_name,
            'thumbnail_image' => $productImage->thumbnail_picture_name,
            'sort_order' => $productImage->picture_id
        ]);
    }
}
