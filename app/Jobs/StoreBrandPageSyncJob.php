<?php

namespace App\Jobs;

use App\Constants\WebStorePageType;
use App\Models\Brand;
use App\Models\Device;
use App\Models\ProductType;
use App\Models\StoreNavigationPage;
use App\Models\StorePageTemplate;
use App\Utils\Navigationutility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

class StoreBrandPageSyncJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 36000;

    protected $store_id;
    protected $page_type;
    protected $template_id;

    /**
     * Create a new job instance.
     */
    public function __construct(
        $store_id,
        $page_type,
        $template_id = null
    ){
        $this->store_id = $store_id;
        $this->page_type = $page_type;
        $this->template_id = $template_id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Testing Here');
        $request = new Request();
        $request->merge(['store_id' => $this->store_id, 'page_type' => $this->page_type, 'template_id' => $this->template_id]);
        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);
        Log::info('Page Type :: ' . $this->template_id);
        $template = StorePageTemplate::findOrFail($this->template_id);
        $page_type = $this->page_type;
        switch ($page_type) {
            case WebStorePageType::BRAND:
                Log::info('Page Type :: ' . $page_type);
                $brands = Brand::where('status', 'active')->orderBy('order_no')->get();
                foreach ($brands as $brand) {
                    Log::info('Brand :: ' . $brand->name);
                    $request['device_brand_id'] = $brand->id;
                    $links = Navigationutility::getLinks($this->store_id, $page_type, $request);

                    $data = [
                        'description' => $request->description ?? '',
                        'title' => $request->title ?? $links['final_title'],
                        'name' => $request->name ?? '',
                        'popularLinks' => $links['popular_link'] ?? [],
                        'otherLinks' => $links['other_link'] ?? [],
                        'alphabeticallySortedPopularLinks' => [],
                        'alphabeticallySortedOtherLinks' => [],
                    ];

                    $request['title'] = $links['final_title'];
                    Log::info('Request Title :: ' . json_encode($request['title']));
                    $request['generated_body'] = Navigationutility::fillTemplateWithData($template->template_code, $data);

                    StoreNavigationPage::updateOrCreate([
                        'store_id' => $this->store_id,
                        'page_type' => $page_type,
                        'device_brand_id' => $brand->id,
                    ],$request->all());
                }
                break;
        }
    }
}
