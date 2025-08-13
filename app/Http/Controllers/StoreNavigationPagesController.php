<?php

namespace App\Http\Controllers;

use App\Constants\StorePageType;
use App\Constants\WebStorePageType;
use App\Models\Store;
use App\Models\StoreNavigationPage;
use App\Models\StorePageTemplate;
use Illuminate\Http\Request;

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
        if(!empty((array) $pages)){
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

        $content = $template->template_code;

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
}
