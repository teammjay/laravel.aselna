<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductSpecificationInfoRequest;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationInfo;
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
        $specification_infos = ProductSpecificationInfo::with('storeType')->get();
        return view('specification_info.list', compact('specification_infos'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $store_types = StoreType::all();
        return view('specification_info.add', compact('store_types'));
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

        ProductSpecificationInfo::create($request->all());

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
        return view('specification_info.add', compact('specification_info','store_types'));
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

        ProductSpecificationInfo::findOrFail($id)->update($request->all());

        return redirect()->route('specification_info.index')
            ->with('success', 'Specification info updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        ProductSpecificationInfo::findOrFail($id)->delete();

        return redirect()->route('specification_info.index')
            ->with('success', 'Specification info deleted successfully.');
    }
}
