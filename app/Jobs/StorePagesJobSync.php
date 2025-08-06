<?php

namespace App\Jobs;

use App\Constants\StorePageType;
use App\Models\PageType;
use App\Models\StoreNavigationPage;
use App\Models\StorePage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StorePagesJobSync implements ShouldQueue
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

        DB::connection('easyesale_db')->table('store_page')->orderBy('id')
            ->chunk(1000, function ($storePages) {
                foreach ($storePages as $storePage) {
                    $this->createStorePage($storePage);
                }
            });

        DB::connection('easyesale_db')->table('store_navigation_page')->orderBy('id')
            ->chunk(1000, function ($storeNavigationPages) {
                foreach ($storeNavigationPages as $storeNavigationPage) {
                    $this->createStoreNavigationPage($storeNavigationPage);
                }
            });
    }

    private function createStorePage(mixed $storePage)
    {
        //Log::info('Store Page ID: ' . json_encode($storePage));
//        $pageType = PageType::where('type', StorePageType::getPageTypeName($storePage->page_type))->first();
        StorePage::updateOrCreate(
            [
                'id' => $storePage->id,
            ],[
                'id' => $storePage->id,
                'store_id' => $storePage->store_id,
                'page_type' => $storePage->page_type,
                'title' => $storePage->title,
                'text' => $storePage->text,
            ]
        );
    }

    private function createStoreNavigationPage(mixed $storeNavigationPage)
    {
        StoreNavigationPage::updateOrCreate(
            [
                'id' => $storeNavigationPage->id,
            ],[
                'id' => $storeNavigationPage->id,
                'store_id' => $storeNavigationPage->store_id,
                'store_image_url' => $storeNavigationPage->store_image_url,
                'page_type' => $storeNavigationPage->page_type,
                'page_id' => $storeNavigationPage->page_id,
                'url_path' => $storeNavigationPage->url_path,
                'parent_page_id' => $storeNavigationPage->parent_page_id,
                'template_id' => $storeNavigationPage->template_id,
                'title' => $storeNavigationPage->title,
                'device_brand_id' => $storeNavigationPage->device_brand_id,
                'device_id' => $storeNavigationPage->device_id,
                'product_type_id' => $storeNavigationPage->product_type_id,
                'generated_body' => $storeNavigationPage->generated_body,
                'keywords' => $storeNavigationPage->keywords,
                'meta_title' => $storeNavigationPage->meta_title,
                'meta_description' => $storeNavigationPage->meta_description,
                'child_ids' => $storeNavigationPage->child_ids,
                'description' => $storeNavigationPage->description,
                'image_id' => $storeNavigationPage->image_id,
                'synchronize' => (bool)$storeNavigationPage->synchronize,
                'update_image' => (bool)$storeNavigationPage->update_image
            ]
        );
    }

}
