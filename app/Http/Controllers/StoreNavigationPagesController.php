<?php

namespace App\Http\Controllers;

use App\Constants\StorePageType;
use App\Constants\WebStorePageType;
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
        $pageTemplates = StorePageTemplate::all();
        return view('store_navigation_pages.add', compact('stores', 'pageTemplates'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'store_id' => 'required',
            'template_id' => 'required',
            'content' => 'required'
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
        $pageTemplates = StorePageTemplate::all();
        return view('store_navigation_pages.add', compact('navigation_page', 'stores', 'pageTemplates'));
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

        $links = $this->getLinks($request->store_id, $request->page_type);

        $data = [
            'description' => $request->description ?? '',
            'title' => $request->title ?? '',
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
            'content' => 'required'
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

    private function getLinks(mixed $store_id, mixed $page_type)
    {
        $finalLinkArray = [];
        switch ($page_type) {
//            case WebStorePageType::ALL_PRODUCT_TYPES:
//                $title = "Product Category";
//                $name = $title;
//                $detailIdFunction = function ($x) {
//                    return $x->getProductType()->getId();
//                };
//                $linkFunction = function ($x) use ($productTypeCategoryPages, $skipDevicePage) {
//                    return new PageExternalLink(
//                        $x->getPageId(),
//                        $x->getProductType()->getName(),
//                        getImageUrl($productTypeCategoryPages[$x->getProductType()->getId()]->getStoreImageUrl()),
//                        ($skipDevicePage ? "/collections/" : "/pages/") . $x->getUrlPath(),
//                        true
//                    );
//                };
//                $alwaysUpdate = true;
//                $popularPage = true;
//                break;
//
//            case WebStorePageType::PRODUCT_TYPE_BRANDS:
//                $productType = $productTypeCategoryPage->getProductType();
//                $title = $productType->getName();
//                $name = $title;
//                $detailIdFunction = function ($x) {
//                    return $x->getDeviceBrand()->getId();
//                };
//                $linkFunction = function ($x) use ($brandCategoryPages) {
//                    return new PageExternalLink(
//                        $x->getPageId(),
//                        $x->getDeviceBrand()->getName(),
//                        getImageUrl($brandCategoryPages[$x->getDeviceBrand()->getId()]->getStoreImageUrl()),
//                        "/pages/" . $x->getUrlPath(),
//                        $x->getDeviceBrand()->getSortOrder() <= 10
//                    );
//                };
//                $popularPage = true;
//                break;
//
//            case WebStorePageType::PRODUCT_TYPE_BRAND_DEVICES:
//                $productType = $productTypeCategoryPage->getProductType();
//                $brand = $brandCategoryPage->getDeviceBrand();
//                $title = $productType->getName() . " for " . $brand->getName();
//                $name = $brand->getName();
//                $detailIdFunction = function ($x) {
//                    return $x->getDevice()->getId();
//                };
//                $linkFunction = function ($x) use ($deviceCategoryPages, $productTypeCategoryPage) {
//                    return new PageExternalLink(
//                        $x->getPageId(),
//                        $x->getDevice()->getModelName(),
//                        getImageUrl($deviceCategoryPages[$x->getDevice()->getId()]->getStoreImageUrl()),
//                        "/collections/" . $x->getUrlPath() . "/" . $productTypeCategoryPage->getProductType()->getSeoName(),
//                        $x->getDevice()->getPopularDevice() !== null
//                    );
//                };
//                $popularPage = isBrandPopular($brand);
//                break;

            case WebStorePageType::ALL_BRANDS:
                $links =  StoreNavigationPage::where('store_id', $store_id)
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

//            case WebStorePageType::BRAND_DEVICES:
//                if ($useCategorPage) {
//                    $navigationPage = $brandCategoryPage;
//                }
//                $brand = $brandCategoryPage->getDeviceBrand();
//                $title = $brand->getName();
//                $name = $brand->getName();
//                $popularPage = isBrandPopular($brand);
//
//                $detailIdFunction = function ($x) {
//                    return $x->getDevice()->getId();
//                };
//                $linkFunction = function ($x) use ($deviceCategoryPages, $skipDevicePage) {
//                    return new PageExternalLink(
//                        $x->getPageId(),
//                        $x->getDevice()->getModelName(),
//                        getImageUrl($deviceCategoryPages[$x->getDevice()->getId()]->getStoreImageUrl()),
//                        ($skipDevicePage ? "/collections/" : "/pages/") . $x->getUrlPath(),
//                        $x->getDevice()->getPopularDevice() !== null
//                    );
//                };
//                break;
//
//            case WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES:
//                if ($useCategorPage) {
//                    $navigationPage = $deviceCategoryPage;
//                }
//                $brand = $brandCategoryPage->getDeviceBrand();
//                $device = $deviceCategoryPage->getDevice();
//                $modelName = $device->getModelName();
//                $brandName = $brand->getName();
//                $title = $brandName . " " . $modelName . " Accessories";
//                $name = $title;
//                $popularPage = isDevicePopular($device);
//                $detailIdFunction = function ($x) {
//                    return $x->getProductType()->getId();
//                };
//                $linkFunction = function ($x) use ($productTypeCategoryPages, $deviceCategoryPage) {
//                    return new PageExternalLink(
//                        $x->getPageId(),
//                        $x->getProductType()->getName(),
//                        getImageUrl($productTypeCategoryPages[$x->getProductType()->getId()]->getStoreImageUrl()),
//                        "/collections/" . $deviceCategoryPage->getUrlPath() . "/" . $x->getProductType()->getSeoName(),
//                        true
//                    );
//                };
//                break;

            default:
                break;
        }

        return $finalLinkArray;
    }
}
