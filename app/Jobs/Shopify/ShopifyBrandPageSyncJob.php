<?php

namespace App\Jobs\Shopify;

use App\Constants\WebStorePageType;
use App\Jobs\Concerns\TrackableJob;
use App\Models\Brand;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreCategoryImage;
use App\Models\StoreDeviceType;
use App\Models\StoreNavigationPage;
use App\Models\StorePageTemplate;
use App\Services\Auth\ShopifyAuthService;
use App\Services\CriticalErrorService;
use App\Utils\FileUtility;
use App\Utils\Navigationutility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery\Exception;

class ShopifyBrandPageSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 36000;

    protected $store_id;
    protected $page_type;
    protected $template_id;
    private $store;
    private ShopifyAuthService $shopifyAuthService;
    private $publications;

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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Store Brand Pages ']);

        try {
            $this->setVariables();
            Log::info('Testing Here');
            $request = new Request();
            $request->merge(['store_id' => $this->store_id, 'page_type' => $this->page_type, 'template_id' => $this->template_id]);
            $created_by = auth()->user()->name ?? 'admin';
            $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);
            Log::info('Page Type :: ' . $this->template_id);
            $template = StorePageTemplate::findOrFail($this->template_id);
            $compatibleDeviceTypes = StoreDeviceType::where('store_id', $this->store_id)
                ->where('purpose', 'listing')
                ->pluck('device_type_id')
                ->toArray();

            $page_type = $this->page_type;
            switch ($page_type) {
                case WebStorePageType::BRAND_DEVICES:
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
                        Log::info('Brand :: ' . $brand->name . ' Generated Body :: ' . $request['generated_body']);

                        StoreNavigationPage::updateOrCreate([
                            'store_id' => $this->store_id,
                            'page_type' => $page_type,
                            'device_brand_id' => $brand->id,
                        ], $request->all());
                    }
                    break;
                case WebStorePageType::BRAND:
                    Log::info('Page Type :: ' . $page_type);
                    $this->getPuplications();
                    $brands = Brand::leftJoin('store_navigation_page', function ($join) {
                        $join->on('store_navigation_page.device_brand_id', '=', 'brands.id')
                            ->where('store_navigation_page.store_id', $this->store_id)
                            ->where('store_navigation_page.page_type', WebStorePageType::BRAND);
                    })
                        ->where('status', 'active')
                        ->where(function ($subquery) {
                            $subquery->whereNull('store_navigation_page.id')
                                ->orWhereColumn('store_navigation_page.updated_at','<', 'brands.updated_at');
                        })
                        ->orderBy('order_no')->get();

                    foreach ($brands as $brand) {
                        $title = $brand->name;
                        $handle = null;

                        $collectionData['collection'] = [
                            'title' => $title,
                        ];
                        $storePage = StoreNavigationPage::where('store_id', $this->store_id)
                            ->where('page_type', WebStorePageType::BRAND)
                            ->where('device_brand_id', $brand->id)
                            ->first();

                        if (isset($storePage)) {
                            $handle = $storePage->url_path;
                        }

                        if (!isset($handle)) {
                            $handle = $this->generateShopifyHandle($title);
                        }

                        $path = '/store-images/' . $this->store_id . '/collections/' . $handle . '.jpg';

                        try {
                            $imagePath = FileUtility::uploadImage($this->store, 'app/public/brand_images/' . $brand->image, $path);
                        } catch (\Exception $e) {
                            $imagePath = '';
                        }

                        if ($imagePath) {
                            $collectionData['collection']['image'] = [
                                'src' => $imagePath,
                            ];
                        }

                        if ($alreadyCollection = $this->shopifyAuthService->getCollectionByHandle($handle)) {
                            Log::info('Collection already exists, updating collection', ['handle' => $handle]);
                            $collectionData['collection']['id'] = $alreadyCollection['id'];
                            $collectionData['collection']['handle'] = $handle;
                            Log::info('Collection data update', ['collection_data' => $collectionData]);
                            $response = $this->shopifyAuthService->updateCollection($collectionData);

                            if (isset($response['data']['collectionUpdate'])) {
                                $collection = $response['data']['collectionUpdate']['collection'];
                            }
                        } else {
                            Log::info('Creating new collection', ['handle' => $handle]);

                            foreach ($this->publications as $publication) {
                                $collectionData['collection']['publications'][]['publicationId'] = $publication;
                            }

                            $collectionData['collection']['handle'] = $handle;
                            Log::info('Collection data creation', ['collection_data' => $collectionData]);
                            $response = $this->shopifyAuthService->createCollection($collectionData);

                            if (isset($response['data']['collectionCreate'])) {
                                $collection = $response['data']['collectionCreate']['collection'];
                            }
                        }

                        if (isset($collection)) {
                            $id = str_replace('gid://shopify/Collection/', '', $collection['id']);
                            $imageUrl = $collection['image']['url'] ?? '';
                            StoreNavigationPage::updateOrCreate(
                                [
                                    'store_id' => $this->store_id,
                                    'page_type' => WebStorePageType::BRAND,
                                    'device_brand_id' => $brand->id,
                                ],
                                [
                                    'store_id' => $this->store_id,
                                    'page_type' => WebStorePageType::BRAND,
                                    'device_brand_id' => $brand->id,
                                    'url_path' => $handle,
                                    'store_image_url' => $imageUrl,
                                    'title' => $title,
                                    'page_id' => $id,
                                ]
                            );
                        }
                    }
                    break;
                case WebStorePageType::DEVICE:
                    $this->getPuplications();
                    Device::select('device.*')->leftJoin('store_navigation_page', function ($join) {
                        $join->on('store_navigation_page.device_id', '=', 'device.id')
                            ->where('store_navigation_page.store_id', $this->store_id)
                            ->where('store_navigation_page.page_type', WebStorePageType::DEVICE);
                    })->leftJoin(
                        \DB::raw('(SELECT device_id, MAX(updated_at) AS last_interface_update FROM device_interface GROUP BY device_id) di'),
                        function ($join) {
                            $join->on('di.device_id', '=', 'device.id');
                        })->where('status', 'active')->where(function ($subquery) {
                        $subquery->whereNull('store_navigation_page.id')
                            ->orWhereRaw('store_navigation_page.updated_at < GREATEST(device.updated_at, COALESCE(di.last_interface_update, "1970-01-01"))');
                    })->whereIn('device_type_id', $compatibleDeviceTypes)
                        ->orderBy('device.id')->chunk(100, function ($devices) use ($page_type, $template, $request) {
                            foreach ($devices as $device) {
                                $handle = null;
                                $brand = $device->brand;
                                $title = $brand->name . ' ' . $device->name . ' Accessories';

                                Log::info('Device :: ' . $device->name . ' Brand :: ' . $brand->name);

                                $storePage = StoreNavigationPage::where('store_id', $this->store_id)
                                    ->where('page_type', WebStorePageType::DEVICE)
                                    ->where('device_id', $device->id)
                                    ->first();

                                if (isset($storePage)) {
                                    $handle = $storePage->url_path;
                                }

                                if (!isset($handle)) {
                                    $handle = $this->generateShopifyHandle($brand->name . ' ' . $device->name);
                                }

                                $collectionData['collection'] = [
                                    'title' => $title,
                                ];
                                $collectionData['collection']['ruleSet']['appliedDisjunctively'] = true;
                                $path = '/store-images/' . $this->store->id . '/collections/' . $handle . '.jpg';
                                $collection = null;

                                try {
                                    $imagePath = FileUtility::uploadImage($this->store, 'app/public/device_images/' . $device->image, $path);
                                } catch (\Exception $e) {
                                    $imagePath = '';
                                }
                                Log::info('Image path for device', ['device_id' => $device->id, 'image_path' => $imagePath]);

                                if ($imagePath) {
                                    $collectionData['collection']['image'] = [
                                        'src' => $imagePath,
                                    ];
                                } else {
                                    Log::error('Image upload failed for device', ['device_id' => $device->id, 'image' => $device->image]);
                                }

                                // fetch 20
                                $interfaces = DeviceInterface::where('device_id', $device->id)->limit(60)->get();
                                foreach ($interfaces as $interface) {
                                    $collectionData['collection']['ruleSet']['rules'][] = [
                                        'column' => 'TAG',
                                        'relation' => 'EQUALS',
                                        'condition' => $interface->interface_id,
                                    ];
                                }

                                $metafields = $this->getDeviceMetafields($device);

                                $collectionData['collection']['metafields'] = $metafields;

                                if ($alreadyCollection = $this->shopifyAuthService->getCollectionByHandle($handle)) {
                                    Log::info('Collection already exists, updating collection', ['handle' => $handle]);
                                    $collectionData['collection']['id'] = $alreadyCollection['id'];
                                    $collectionData['collection']['handle'] = $handle;
                                    Log::info('Collection data update', ['collection_data' => $collectionData]);
                                    $response = $this->shopifyAuthService->updateCollection($collectionData);

                                    if (isset($response['data']['collectionUpdate'])) {
                                        $collection = $response['data']['collectionUpdate']['collection'];
                                    }
                                } else {
                                    Log::info('Creating new collection', ['handle' => $handle]);

                                    foreach ($this->publications as $publication) {
                                        $collectionData['collection']['publications'][]['publicationId'] = $publication;
                                    }

                                    $collectionData['collection']['handle'] = $handle;
                                    Log::info('Collection data creation', ['collection_data' => $collectionData]);
                                    $response = $this->shopifyAuthService->createCollection($collectionData);

                                    if (isset($response['data']['collectionCreate'])) {
                                        $collection = $response['data']['collectionCreate']['collection'];
                                    }
                                }
                                Log::info('Collection ::' . json_encode($collection));
                                if (isset($collection)) {
                                    $id = str_replace('gid://shopify/Collection/', '', $collection['id']);
                                    $imageUrl = $collection['image']['url'] ?? '';
                                    StoreNavigationPage::updateOrCreate(
                                        [
                                            'store_id' => $this->store_id,
                                            'page_type' => WebStorePageType::DEVICE,
                                            'device_id' => $device->id,
                                        ],
                                        [
                                            'store_id' => $this->store_id,
                                            'page_type' => WebStorePageType::DEVICE,
                                            'device_id' => $device->id,
                                            'device_brand_id' => $device->brand_id,
                                            'url_path' => $handle,
                                            'store_image_url' => $imageUrl,
                                            'title' => $title,
                                            'page_id' => $id,
                                        ]
                                    );
                                }
                            }
                        });
                    break;
                case WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES:
                    Device::where('status', 'active')->whereIn('device_type_id', $compatibleDeviceTypes)->orderBy('id')->chunk(100, function ($devices) use ($page_type, $template, $request) {
                        foreach ($devices as $device) {
                            Log::info('Device :: ' . $device->name);
                            $deviceCollectionPage = StoreNavigationPage::where('store_id', $this->store_id)
                                ->where('page_type', WebStorePageType::DEVICE)
                                ->where('device_id', $device->id)
                                ->first();
                            if (!isset($deviceCollectionPage)) {
                                Log::info('Device Collection Page not found for device id :: ' . $device->id);
                                continue;
                            }
                            $request['device_brand_id'] = $device->brand->id;
                            $request['device_id'] = $device->id;
                            $links = Navigationutility::getLinks($this->store_id, $page_type, $request);
                            $request['title'] = $device->brand->name . ' ' . $device->name . ' Accessories';
                            $data = [
                                'description' => $request->description ?? '',
                                'title' => $links['final_title'],
                                'name' => $request->name ?? '',
                                'popularLinks' => $links['popular_link'] ?? [],
                                'otherLinks' => $links['other_link'] ?? [],
                                'alphabeticallySortedPopularLinks' => [],
                                'alphabeticallySortedOtherLinks' => [],
                            ];
                            $request['generated_body'] = Navigationutility::fillTemplateWithData($template->template_code, $data);

                            StoreNavigationPage::updateOrCreate([
                                'store_id' => $this->store_id,
                                'page_type' => $page_type,
                                'device_brand_id' => $device->brand->id,
                                'device_id' => $device->id,
                            ], $request->all());
                        }
                    });
                    break;
                case WebStorePageType::PRODUCT_TYPE:
                    $this->getPuplications();
                    $productTypes = ProductType::whereIn('parent_id', function ($subquery) {
                        $subquery->select('id')->from('product_type')->where('code', 'cea');
                    })->whereNotNull('sort_order')->orderBy('sort_order', 'asc')->get();

                    Log::info("Product Types Count :: " . count($productTypes));
                    foreach ($productTypes as $productType) {
                        $title = $productType->name;
                        $collectionData['collection'] = [
                            'title' => $title,
                        ];
                        $handle = null;
                        $collectionData['collection']['ruleSet']['appliedDisjunctively'] = true;

                        $storePage = StoreNavigationPage::where('store_id', $this->store_id)
                            ->where('page_type', WebStorePageType::PRODUCT_TYPE)
                            ->where('product_type_id', $productType->id)
                            ->first();

                        if (isset($storePage)) {
                            $handle = $storePage->url_path;
                        }

                        if (!isset($handle)) {
                            $handle = $this->generateShopifyHandle($title);
                        }

                        $collectionData['collection']['ruleSet']['rules'][] = [
                            'column' => 'TAG',
                            'relation' => 'EQUALS',
                            'condition' => $productType->id,
                        ];
                        $imagePath = null;

                        try {
                            $path = '/store-images/' . $this->store->id . '/collections/' . $handle . '.jpg';
                            $imagePath = $productType->image->getFileUrl();
                            Log::info('Image :: ' . $imagePath);
                            $imagePath = FileUtility::uploadImage($this->store, 'app/public/' . $imagePath, $path);

                            if($this->store_id){
                                $storeCategoryImage = StoreCategoryImage::with('imageObj')->where('store_id',$this->store_id)->where('category_id',$productType->id)->first();
                                if(!empty($storeCategoryImage)){
                                    if(isset($storeCategoryImage->imageObj) && !empty($storeCategoryImage->imageObj)){
                                        //$imagePath = asset('storage/store_category_images/'. $storeCategoryImage->imageObj->name);
                                        $imagePath = $storeCategoryImage->imageObj->getFileUrl();
                                        $imagePath = FileUtility::uploadImage($this->store, 'app/public/' . $imagePath, $path);
                                    }
                                }
                            }

                            Log::info('Final Image :: ' . $imagePath);
                        } catch (\Exception $e) {
                            $imagePath = '';
                        }
                        Log::info('Image path for device', ['image_path' => $imagePath]);

                        if ($imagePath) {
                            $collectionData['collection']['image'] = [
                                'src' => $imagePath,
                            ];
                        }

                        $collection = null;
                        Log::info('Collection Name :: ' . $title . ' Handle :: ' . $handle);

                        if ($alreadyCollection = $this->shopifyAuthService->getCollectionByHandle($handle)) {
                            Log::info('Collection already exists, updating collection', ['handle' => $handle]);
                            $collectionData['collection']['id'] = $alreadyCollection['id'];
                            $collectionData['collection']['handle'] = $handle;
                            Log::info('Collection data update', ['collection_data' => $collectionData]);
                            $response = $this->shopifyAuthService->updateCollection($collectionData);

                            if (isset($response['data']['collectionUpdate'])) {
                                $collection = $response['data']['collectionUpdate']['collection'];
                            }
                        } else {
                            Log::info('Creating new collection', ['handle' => $handle]);

                            foreach ($this->publications as $publication) {
                                $collectionData['collection']['publications'][]['publicationId'] = $publication;
                            }

                            $collectionData['collection']['handle'] = $handle;
                            Log::info('Collection data creation', ['collection_data' => $collectionData]);
                            $response = $this->shopifyAuthService->createCollection($collectionData);

                            if (isset($response['data']['collectionCreate'])) {
                                $collection = $response['data']['collectionCreate']['collection'];
                            }
                        }

                        if (isset($collection)) {
                            $id = str_replace('gid://shopify/Collection/', '', $collection['id']);
                            $imageUrl = $collection['image']['url'] ?? '';
                            StoreNavigationPage::updateOrCreate(
                                [
                                    'store_id' => $this->store_id,
                                    'page_type' => WebStorePageType::PRODUCT_TYPE,
                                    'url_path' => $handle,
                                ],
                                [
                                    'store_id' => $this->store_id,
                                    'page_type' => WebStorePageType::PRODUCT_TYPE,
                                    'product_type_id' => $productType->id,
                                    'url_path' => $handle,
                                    'store_image_url' => $imageUrl,
                                    'title' => $title,
                                    'page_id' => $id,
                                ]
                            );
                        }
                    }
                    break;
                case WebStorePageType::PRODUCT_TYPE_BRANDS:
                    $productTypes = ProductType::whereNull('parent_id')->whereNot('code','cea')
                        ->whereNotNull('sort_order')->orderBy('sort_order', 'asc')->get();

                    foreach ($productTypes as $productType) {
                        $request['product_type'] = $productType->id;
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
                            'product_type_id' => $productType->id,
                        ], $request->all());
                    }
                case WebStorePageType::ALL_BRANDS:
                    $links = Navigationutility::getLinks($this->store_id, $this->page_type, $request);

                    $data = [
                        'description' => $request->description ?? '',
                        'title' => $request->title ?? $links['final_title'],
                        'name' => $request->name ?? '',
                        'popularLinks' => $links['popular_link'] ?? [],
                        'otherLinks' => $links['other_link'] ?? [],
                        'alphabeticallySortedPopularLinks' => [],
                        'alphabeticallySortedOtherLinks' => [],
                    ];

                    $request['generated_body'] = Navigationutility::fillTemplateWithData($template->template_code, $data);

                    StoreNavigationPage::updateOrCreate([
                        'store_id' => $this->store_id,
                        'page_type' => $page_type
                    ], $request->all());
                    break;
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Store Brand Pages Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['store_id' => $this->store_id, 'page_type' => $this->page_type, 'template_id' => $this->template_id]);
        }
    }

    private function setVariables()
    {
        Log::info('Store ID :: ' . $this->store_id);
        $this->store = Store::find($this->store_id);
        Log::info('Store Details :: ' . json_encode($this->store));

        $this->shopifyAuthService = new ShopifyAuthService($this->store->secret_key,$this->store->service_url);
    }
    function generateShopifyHandle($title) {
        // Convert to lowercase
        $handle = mb_strtolower($title, 'UTF-8');

        // Normalize accented characters (e.g., é → e)
        $handle = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $handle);

        // Replace apostrophes
        $handle = preg_replace("/'/", '', $handle);

        // Replace any non-alphanumeric characters (excluding spaces and dots) with nothing
        $handle = preg_replace("/[^a-z0-9\s.]/", '', $handle);

        // Replace dots with hyphens (e.g., 12.3 → 12-3)
        $handle = str_replace('.', '-', $handle);

        // Replace whitespace with hyphens
        $handle = preg_replace("/[\s]+/", '-', $handle);

        // Remove multiple consecutive hyphens
        $handle = preg_replace("/-+/", '-', $handle);

        // Trim hyphens from beginning and end
        $handle = trim($handle, '-');

        return $handle;
    }

    private function getPuplications()
    {
        $publications = $this->shopifyAuthService->getPublications();

        $publicationIds = [];
        foreach ($publications['data']['publications']['edges'] as $publication) {
            $publicationIds[] = $publication['node']['id'];
        }
        $this->publications = $publicationIds;
    }

    private function getDeviceMetafields(mixed $device)
    {
        Log::info('Metafield Addition start for device :: ' . $device->name);
        $metafields = [];
        $metafields[] = [
            'namespace' => 'global',
            'key' => 'device_model_name',
            'value' => $device->name,
            'type' => 'single_line_text_field'
        ];

        $metafields[] = [
            'namespace' => 'global',
            'key' => 'device_brand_name',
            'value' => $device->brand->name,
            'type' => 'single_line_text_field'
        ];

        $metafields[] = [
            'namespace' => 'global',
            'key' => 'device_code',
            'value' => $device->code,
            'type' => 'single_line_text_field'
        ];

        $device_name = $device->brand->name . ' ' . $device->name;
        // replace Apple from the device name
        $device_name = str_replace('Apple ', '', $device_name);
        $device_name = str_replace('apple ', '', $device_name);


        $metafields[] = [
            'namespace' => 'global',
            'key' => 'device_name',
            'value' => $device_name,
            'type' => 'single_line_text_field'
        ];

        $metafields[] = [
            'namespace' => 'global',
            'key' => 'device_id',
            'value' => (string)$device->id,
            'type' => 'single_line_text_field'
        ];
        //Need to create the above json and save in metafield with namespace global and key device_categories_json
        $productTypes = ProductType::whereIn('parent_id', function ($subquery) {
            $subquery->select('id')->from('product_type')->where('code', 'cea');
        })->whereNotNull('sort_order')->orderBy('sort_order', 'asc')->get();

        $productTypeMap = $this->getProductTypeMap($device);
        $interfaces = $this->getInterfacesForDevice($device);

        foreach ($productTypes as $productType) {
            if (!key_exists($productType->id, $productTypeMap)) continue;
            $page = StoreNavigationPage::where('store_id', $this->store_id)
                ->where('page_type', WebStorePageType::PRODUCT_TYPE)
                ->where('product_type_id', $productType->id)
                ->first();

            $shildrenProductTypes = ProductType::where('parent_id', $productType->id)->orderBy('sort_order', 'asc')->get();
            $subCategories = [];
            foreach ($shildrenProductTypes as $childproductType) {
                if (!key_exists($childproductType->id, $productTypeMap)) continue;
                $subCategories[] = [
                    'name' => $childproductType->name,
                    'tag' => $this->generateShopifyHandle($childproductType->name)
                ];
            }

            $categories[] = [
                'name' => $productType->name,
                'imageUrl' => $page->store_image_url ?? '',
                'tag' => $page->url_path ?? '',
                'sortOrder' => $productType->sort_order,
                'subCategories' => $subCategories
            ];
        }

        $metafields[] = [
            'namespace' => 'global',
            'key' => 'device_categories_json',
            'value' => json_encode($categories),
            'type' => 'json'
        ];

        $metafields[] = [
            'namespace' => 'global',
            'key' => 'device_categories_json',
            'value' => json_encode($interfaces),
            'type' => 'single_line_text_field'
        ];

        $metafields[] = [
            'namespace' => 'global',
            'key' => 'device_type',
            'value' => $device->deviceType->name ?? '',
            'type' => 'single_line_text_field'
        ];

        return $metafields;
    }

    /**
     * @param mixed $device
     * @return array
     */
    public function getProductTypeMap(mixed $device): array
    {
        $productTypeMapQuery = DeviceInterface::join('product_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id')
            ->join('products', 'products.product_id', '=', 'product_interface.product_id')
            ->join('product_type as child_pt', 'child_pt.id', '=', 'products.product_type')
            ->leftJoin('product_type as parent_pt', 'parent_pt.id', '=', 'child_pt.parent_id')
            ->join('device', 'device_interface.device_id', '=', 'device.id')
            ->where('device.status', 'active')
            ->where('products.product_status', 'active')
            ->where('device_interface.device_id', $device->id)
            ->groupBy(\DB::raw('child_pt.id'))
            ->select(
                \DB::raw('child_pt.id as parent_id'),
                \DB::raw('COUNT(products.product_id) as product_count')
            )
            ->get();

        $parentProductTypeMapQuery = DeviceInterface::join('product_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id')
            ->join('products', 'products.product_id', '=', 'product_interface.product_id')
            ->join('product_type as child_pt', 'child_pt.id', '=', 'products.product_type')
            ->leftJoin('product_type as parent_pt', 'parent_pt.id', '=', 'child_pt.parent_id')
            ->join('device', 'device_interface.device_id', '=', 'device.id')
            ->where('device.status', 'active')
            ->where('products.product_status', 'active')
            ->where('device_interface.device_id', $device->id)
            ->groupBy(\DB::raw('parent_pt.id'))
            ->select(
                \DB::raw('parent_pt.id as parent_id'),
                \DB::raw('COUNT(products.product_id) as product_count')
            )
            ->get();

        $productTypeMap = [];
        foreach ($productTypeMapQuery as $ptm) {
            if ($ptm->product_count <= 0) {
                continue;
            }
            $productTypeMap[$ptm->parent_id] = $ptm->product_count;
        }

        foreach ($parentProductTypeMapQuery as $ptm) {
            if ($ptm->product_count <= 0) {
                continue;
            }
            $productTypeMap[$ptm->parent_id] = $ptm->product_count;
        }
        return $productTypeMap;
    }

    private function getInterfacesForDevice(mixed $device)
    {
        return DeviceInterface::where('device_id', $device->id)->pluck('interface_id')->toArray();
    }
}
