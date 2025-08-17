<?php

namespace App\Http\Controllers;

use App\Constants\StorePageType;
use App\Constants\WebStorePageType;
use App\Models\Brand;
use App\Models\Device;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreNavigationPage;
use App\Models\StorePageTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

class StoreNavigationPagesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view('store_navigation_pages.list');
    }

    public function filter(Request $request)
    {
        $draw = $request['draw'];
        $start = $request['start'];
        $perPage = $request['length'];
        $columnIndex = $request['order'][0]['column'];
        $columnName = $request['columns'][$columnIndex]['data'];
        $columnSortOrder = $request['order'][0]['dir'];

        $query = StoreNavigationPage::select('*');

        $totalRecords = $query->count();
        $pages = $query->orderBy($columnName, $columnSortOrder)
            ->skip($start)
            ->take($perPage)
            ->get();
        $totalRecordwithFilter = $totalRecords;

        $data = [];
        if (!empty((array)$pages)) {
            foreach ($pages as $page) {
                $data[] = [
                    'id' => $page->id,
                    'title' => $page->title,
                    'store_name' => $page->store->name ?? '',
                    'navigation_type' => WebStorePageType::getPageTypeName($page->page_type),
                    'created_by' => $page->created_by,
                    'updated_by' => $page->updated_by,
                    'created_at' => $page->created_at->format('Y-m-d H:i:s'),
                    'action' => ''
                ];
            }
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecordwithFilter,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data
        );

        return $response;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        $brands = Brand::where('status', 1)->orderBy('name', 'asc')->get();
        $pageTemplates = StorePageTemplate::all();
        $devices = Device::all();
        $productTypes = ProductType::orderBy('name')->get();
        return view('store_navigation_pages.add', compact('stores', 'pageTemplates', 'brands', 'devices', 'productTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required',
            'template_id' => 'required',
            'generated_body' => 'required'
        ]);

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        StoreNavigationPage::create($request->all());

        return redirect()->route('store_navigation_page.index')
            ->with('success', 'Store navigation page created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $navigation_page = StoreNavigationPage::findOrFail($id);
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        $brands = Brand::where('status', 1)->orderBy('name', 'asc')->get();
        $pageTemplates = StorePageTemplate::all();
        $devices = Device::all();
        $productTypes = ProductType::orderBy('name')->get();
        return view('store_navigation_pages.add', compact('navigation_page', 'stores', 'pageTemplates', 'brands', 'devices', 'productTypes'));
    }

    //generateContent
    public function generateContent(Request $request)
    {
        $request->validate([
            'store_id' => 'required',
            'page_type' => 'required',
            'template_id' => 'required',
        ]);

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        $template = StorePageTemplate::findOrFail($request->template_id);

        $links = $this->getLinks($request->store_id, $request->page_type, $request);

        $data = [
            'description' => $request->description ?? '',
            'title' => $request->title ?? $links['final_title'],
            'name' => $request->name ?? '',
            'popularLinks' => $links['popular_link'] ?? [],
            'otherLinks' => $links['other_link'] ?? [],
            'alphabeticallySortedPopularLinks' => [],
            'alphabeticallySortedOtherLinks' => [],
        ];

        Log::info('Data :: ' . json_encode($data));

        $content = $this->fillTemplateWithData($template->template_code, $data);

        return response()->json(['content' => $content]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required',
            'store_id' => 'required',
            'template_id' => 'required',
            'generated_body' => 'required'
        ]);

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        StoreNavigationPage::findOrFail($id)->update($request->all());

        return redirect()->route('store_navigation_page.index')
            ->with('success', 'Store navigation page updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        StoreNavigationPage::findOrFail($id)->delete();

        return redirect()->route('store_navigation_page.index')
            ->with('success', 'Store navigation page deleted successfully.');
    }

    function fillTemplateWithData(string $templateString, $templateData)
    {
        try {
            return Blade::render($templateString, $templateData);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }
    }

    private function getLinks(mixed $store_id, mixed $page_type, Request $request): array
    {
        $finalLinkArray = [];
        switch ($page_type) {
            case WebStorePageType::ALL_PRODUCT_TYPES:
                $finalLinkArray['final_title'] = 'Product Category';
                break;
            case WebStorePageType::PRODUCT_TYPE_BRANDS:
                $productType = ProductType::where('id', $request->product_type)->firstOrFail();
                $finalLinkArray['final_title'] = $productType->name;
                $links = StoreNavigationPage::where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::BRAND)
                    ->get();
                foreach ($links as $link) {
                    if ($link->brand->sort_order <= 10) {
                        $finalLinkArray['popular_link'][] = [
                            'id' => $link->id,
                            'title' => $link->brand->name,
                            'imageUrl' => $link->store_image_url,
                            'url' => "/pages/" . $link->url_path,
                            'popular' => true
                        ];
                    } else {
                        $finalLinkArray['other_link'][] = [
                            'id' => $link->id,
                            'title' => $link->brand->name,
                            'imageUrl' => $link->store_image_url,
                            'url' => "/pages/" . $link->url_path,
                            'popular' => false
                        ];
                    }
                }
                break;

            case WebStorePageType::PRODUCT_TYPE_BRAND_DEVICES:
                $device = Device::where('id', $request->device_id)->firstOrFail();
                $finalLinkArray['final_title'] = $device->name . ' for ' . $device->brand->name;
                $links = StoreNavigationPage::where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::DEVICE)
                    ->get();
                foreach ($links as $link) {
                    if ($link->device->order <= 10) {
                        $finalLinkArray['popular_link'][] = [
                            'id' => $link->id,
                            'title' => $link->device->name,
                            'imageUrl' => $link->store_image_url,
                            'url' => "/pages/" . $link->url_path,
                            'popular' => true
                        ];
                    } else {
                        $finalLinkArray['other_link'][] = [
                            'id' => $link->id,
                            'title' => $link->device->name,
                            'imageUrl' => $link->store_image_url,
                            'url' => "/pages/" . $link->url_path,
                            'popular' => false
                        ];
                    }
                }
                break;

            case WebStorePageType::ALL_BRANDS:
                $links = StoreNavigationPage::where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::BRAND)
                    ->get();
                $finalLinkArray['final_title'] = 'Shop By Brand';
                foreach ($links as $link) {
                    if ($link->brand->sort_order <= 10) {
                        $finalLinkArray['popular_link'][] = [
                            'id' => $link->id,
                            'title' => $link->brand->name,
                            'imageUrl' => $link->store_image_url,
                            'url' => "/pages/" . $link->url_path,
                            'popular' => true
                        ];
                    } else {
                        $finalLinkArray['other_link'][] = [
                            'id' => $link->id,
                            'title' => $link->brand->name,
                            'imageUrl' => $link->store_image_url,
                            'url' => "/pages/" . $link->url_path,
                            'popular' => false
                        ];
                    }
                }
                break;

            case WebStorePageType::BRAND_DEVICES:
                $finalLinkArray['final_title'] = '';
                break;

            case WebStorePageType::DEVICE:
                break;

            case WebStorePageType::BRAND:
                $links = StoreNavigationPage::where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES)
                    ->get();
                $finalLinkArray['final_title'] = '';
                foreach ($links as $link) {
                    if ($link->device->order <= 10) {
                        $finalLinkArray['popular_link'][] = [
                            'id' => $link->id,
                            'title' => $link->brand->name,
                            'imageUrl' => $link->store_image_url,
                            'url' => "/pages/" . $link->url_path,
                            'popular' => true
                        ];
                    } else {
                        $finalLinkArray['other_link'][] = [
                            'id' => $link->id,
                            'title' => $link->brand->name,
                            'imageUrl' => $link->store_image_url,
                            'url' => "/pages/" . $link->url_path,
                            'popular' => false
                        ];
                    }
                }
                break;

            case WebStorePageType::PRODUCT_TYPE:
                break;

            case WebStorePageType::DEVICE_PRODUCT_TYPE:
                break;

            case WebStorePageType::ALL_DEVICES:
                break;

            case WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES:
                $links = StoreNavigationPage::where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::PRODUCT_TYPE)
                    ->get();
                $finalLinkArray['final_title'] = '';
                foreach ($links as $link) {
                    $finalLinkArray['popular_link'][] = [
                        'id' => $link->id,
                        'title' => $link->productType->name,
                        'imageUrl' => $link->store_image_url,
                        'url' => "/pages/" . $link->url_path,
                        'popular' => true
                    ];
                }
                break;

            default:
                break;
        }

        return $finalLinkArray;
    }
}
