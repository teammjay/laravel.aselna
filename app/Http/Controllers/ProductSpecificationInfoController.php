<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductSpecificationInfoRequest;
use App\Models\Catalog;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationInfo;
use App\Models\productSpecificationInfoCategoryLink;
use App\Models\ProductType;
use App\Models\StoreType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductSpecificationInfoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $specification_infos = ProductSpecificationInfo::with('storeType','productTypes.productType')->get();
        return view('specification_info.list', compact('specification_infos'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $store_types = StoreType::all();
        $product_types = ProductType::with('children')->whereIn('parent_id', function ($subquery) {
            $subquery->select('id')->from('product_type')->where('code', 'cea');
        })->orderBy('name', 'asc')->get();
        return view('specification_info.add', compact('store_types','product_types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductSpecificationInfoRequest $request)
    {
        $request->validated();

        $request['required'] = $request->has('required') ? 1 : 0;

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        $info = ProductSpecificationInfo::create($request->except(['product_type_id']));
        $info_id = $info->id;

        if($request->has('product_type_id')){

            for($i=0; $i < count($request->product_type_id); $i++){
                $data[] = array(
                    'specification_info_id' => $info_id,
                    'product_type_id' => $request->product_type_id[$i]
                );
            }

            productSpecificationInfoCategoryLink::where('specification_info_id', $info_id)->delete();
            productSpecificationInfoCategoryLink::insert($data);
        }


        return redirect()->route('specification_info.index')
            ->with('success', 'Specification info created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $specification_info = ProductSpecificationInfo::findOrFail($id);
        $store_types = StoreType::all();
        $product_types = ProductType::with('children')->whereIn('parent_id', function ($subquery) {
            $subquery->select('id')->from('product_type')->where('code', 'cea');
        })->orderBy('name', 'asc')->get();

        $info_category = productSpecificationInfoCategoryLink::where('specification_info_id', $id)->get();
        $info_category = $info_category->pluck('product_type_id')->values()->all();
        return view('specification_info.add', compact('specification_info','store_types','product_types','info_category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductSpecificationInfoRequest $request, string $id)
    {
        $request->validated();

        $request['required'] = $request->has('required') ? 1 : 0;

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        ProductSpecificationInfo::findOrFail($id)->update($request->except(['product_type_id']));

        if($request->has('product_type_id')){

            for($i=0; $i < count($request->product_type_id); $i++){
                $data[] = array(
                    'specification_info_id' => $id,
                    'product_type_id' => $request->product_type_id[$i]
                );
            }

            productSpecificationInfoCategoryLink::where('specification_info_id', $id)->delete();
            productSpecificationInfoCategoryLink::insert($data);
        }

        return redirect()->route('specification_info.index')
            ->with('success', 'Specification info updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ProductSpecificationInfo::findOrFail($id)->delete();
        return redirect()->route('specification_info.index')
            ->with('success', 'Specification info deleted successfully.');
    }
}
