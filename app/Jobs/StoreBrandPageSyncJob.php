<?php

namespace App\Jobs;

use App\Constants\WebStorePageType;
use App\Models\Brand;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\Images;
use App\Models\Interfaces;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreNavigationPage;
use App\Models\StorePageTemplate;
use App\Services\Auth\ShopifyAuthService;
use App\Utils\FileUtility;
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
    private $store;
    private ShopifyAuthService $shopifyAuthService;
    private array $publications;

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
        $this->setVariables();
        Log::info('Testing Here');
        $request = new Request();
        $request->merge(['store_id' => $this->store_id, 'page_type' => $this->page_type, 'template_id' => $this->template_id]);
        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);
        Log::info('Page Type :: ' . $this->template_id);
        $template = StorePageTemplate::findOrFail($this->template_id);
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

                    StoreNavigationPage::updateOrCreate([
                        'store_id' => $this->store_id,
                        'page_type' => $page_type,
                        'device_brand_id' => $brand->id,
                    ],$request->all());
                }
                break;
            case WebStorePageType::BRAND:
                Log::info('Page Type :: ' . $page_type);
                $this->getPuplications();
                $brands = Brand::where('status', 'active')->orderBy('order_no')->get();
                foreach ($brands as $brand) {
                    $title = $brand->name;

                    $collectionData['collection'] = [
                        'title' => $title,
                    ];
                    $storePage = StoreNavigationPage::where('store_id', $this->store_id)
                        ->where('page_type', WebStorePageType::BRAND)
                        ->where('device_brand_id', $brand->id)
                        ->first();

                    if (isset($storePage)) {
                        $handle = $storePage->url_path;
                    } else {
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
                        $id =str_replace('gid://shopify/Collection/', '', $collection['id']);
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
                Device::where('status', 'active')->orderBy('id')->chunk(100, function ($devices) use ($page_type, $template, $request) {
                    foreach ($devices as $device) {
                        $brand = $device->brand;
                        $title = $brand->name . ' ' . $device->name . ' Accessories';

                        Log::info('Device :: ' . $device->name . ' Brand :: ' . $brand->name);

                        $storePage = StoreNavigationPage::where('store_id', $this->store_id)
                            ->where('page_type', WebStorePageType::DEVICE)
                            ->where('device_id', $device->id)
                            ->first();

                        if (isset($storePage)) {
                            $handle = $storePage->url_path;
                        } else {
                            $handle = $this->generateShopifyHandle($brand->name . ' ' . $device->name );
                        }

                        $collectionData['collection'] = [
                            'title' => $title,
                        ];
                        $collectionData['collection']['ruleSet']['appliedDisjunctively'] = true;
                        $path = '/store-images/' . $this->store->id . '/collections/' . $handle . '.jpg';
                        $collection = null;

                        if ($device->updated_at <= $storePage->updated_at ?? now()) {
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

                        if ($alreadyCollection = $this->shopifyAuthService->getCollectionByHandle($handle)) {
                            Log::info('Collection already exists, updating collection', ['handle' => $handle]);
                            $collectionData['collection']['id'] = $alreadyCollection['id'];
                            $collectionData['collection']['handle'] = $handle;
                            Log::info('Collection data update', ['collection_data' => $collectionData]);
                            $response = $this->shopifyAuthService->updateCollection($collectionData);

                            if (isset($response['data']['collectionUpdate'])) {
                                $collection = $response['data']['collectionUpdate']['collection'];
                            }
                        }
                        else
                        {
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
                            $id =str_replace('gid://shopify/Collection/', '', $collection['id']);
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
                Device::where('status', 'active')->orderBy('id')->chunk(100, function ($devices) use ($page_type, $template, $request) {
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
                        ],$request->all());
                    }
                });
                break;
            case WebStorePageType::PRODUCT_TYPE:
                $this->getPuplications();
                $productTypes = ProductType::whereIn('parent_id', function ($subquery) {
                    $subquery->select('id')->from('product_type')->where('code', 'cea');
                })->whereNotNull('sort_order')->orderBy('sort_order', 'asc')->get();

                foreach ($productTypes as $productType) {
                    $title = $productType->name;
                    $collectionData['collection'] = [
                        'title' => $title,
                    ];
                    $collectionData['collection']['ruleSet']['appliedDisjunctively'] = true;

                    $storePage = StoreNavigationPage::where('store_id', $this->store_id)
                        ->where('page_type', WebStorePageType::PRODUCT_TYPE)
                        ->where('product_type_id', $productType->id)
                        ->first();

                    if (isset($storePage)) {
                        $handle = $storePage->url_path;
                    } else {
                        $handle = $this->generateShopifyHandle($title);
                    }

                    $collectionData['collection']['ruleSet']['rules'][] = [
                        'column' => 'TAG',
                        'relation' => 'EQUALS',
                        'condition' => $productType->id,
                    ];

                    $collection = null;

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
                        $id =str_replace('gid://shopify/Collection/', '', $collection['id']);
                        $imageUrl = $collection['image']['url'] ?? '';
                        StoreNavigationPage::updateOrCreate(
                            [
                                'store_id' => $this->store_id,
                                'page_type' => WebStorePageType::PRODUCT_TYPE,
                                'product_type_id' => $productType->id,
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
                $productTypes = ProductType::whereIn('parent_id', function ($subquery) {
                    $subquery->select('id')->from('product_type')->where('code', 'cea');
                })->whereNotNull('sort_order')->orderBy('sort_order', 'asc')->get();

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
                    ],$request->all());
                }
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
}
