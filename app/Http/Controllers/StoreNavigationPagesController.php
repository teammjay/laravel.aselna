<?php

namespace App\Http\Controllers;

use App\Constants\StorePageType;
use App\Constants\WebStorePageType;
use App\Jobs\ShopifyIndvPagesSyncJob;
use App\Jobs\StoreBrandPageSyncJob;
use App\Models\Brand;
use App\Models\Device;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreNavigationPage;
use App\Models\StorePageTemplate;
use App\Utils\Navigationutility;
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
        $stores = Store::with('storeType')->where('store_status', 1)->orderBy('name', 'asc')->get();
        $pageTemplates = StorePageTemplate::orderBy('name')->get();
        $productTypes = ProductType::orderBy('name')->get();
        return view('store_navigation_pages.list', compact('stores', 'pageTemplates', 'productTypes'));
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

        if ($request->has('store_id') && $request->input('store_id') != '') {
            $query->where('store_id', $request->input('store_id'));
        }

        if ($request->has('navigation_type') && $request->input('navigation_type') != '') {
            $query->where('page_type', $request->input('navigation_type'));
        }

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
                    'store_name' => $page->store->name . ' - ' . $page->store->storeType->name ?? '',
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
        $pageTemplates = StorePageTemplate::orderBy('name')->get();
        $productTypes = ProductType::orderBy('name')->get();
        $devices = Device::all();
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

        $links = Navigationutility::($request->store_id, $request->page_type, $request);

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

        $content = Navigationutility::fillTemplateWithData($template->template_code, $data);

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

    public function generate_bulk(Request $request)
    {
        $request->validate([
            'store_id' => 'required',
            'page_type' => 'required',
            'template_id' => 'required',
        ]);

        StoreBrandPageSyncJob::dispatch(
            $request->store_id,
            $request->page_type,
            $request->template_id
        );

        return response()->json(['success' => true, 'message' => 'Store navigation pages generated successfully.']);
    }


    public function generate_all_bulk(Request $request)
    {
        $request->validate([
            'store_id' => 'required',
        ]);
        $store = Store::findOrFail($request->store_id);
        $allBrandTemplate = StorePageTemplate::where('name', 'All Brands')->first();
        $brandTemplate = StorePageTemplate::where('name', 'Brand')->first();
        $brandDeviceProductTemplate = StorePageTemplate::where('name', 'Brand Device Product Types')->first();
        $productTypeBrands = StorePageTemplate::where('name', 'Product types brands')->first();
        $brandDevicesTemplate = StorePageTemplate::where('name', 'Brand Devices')->first();

        StoreBrandPageSyncJob::
        withChain([
            new StoreBrandPageSyncJob(
                $request->store_id,
                WebStorePageType::PRODUCT_TYPE,
                $brandTemplate->id
            ),
            new StoreBrandPageSyncJob(
                $request->store_id,
                WebStorePageType::DEVICE,
                $brandTemplate->id
            ),
            new StoreBrandPageSyncJob(
                $request->store_id,
                WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES,
                $brandDeviceProductTemplate->id
            ),
            new ShopifyIndvPagesSyncJob(
                $request->store_id,
                WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES
            ),
            new StoreBrandPageSyncJob(
                $request->store_id,
                WebStorePageType::PRODUCT_TYPE_BRANDS,
                $productTypeBrands->id
            ),
            new ShopifyIndvPagesSyncJob(
                $request->store_id,
                WebStorePageType::PRODUCT_TYPE_BRANDS
            ),
            new StoreBrandPageSyncJob(
                $request->store_id,
                WebStorePageType::BRAND_DEVICES,
                $brandDevicesTemplate->id
            ),
            new ShopifyIndvPagesSyncJob(
                $request->store_id,
                WebStorePageType::BRAND_DEVICES
            ),
        ])->dispatch(
            $request->store_id,
            WebStorePageType::BRAND,
            $brandTemplate->id
        );

        return response()->json(['success' => true, 'message' => 'Store navigation pages generating successfully.']);
    }
}
