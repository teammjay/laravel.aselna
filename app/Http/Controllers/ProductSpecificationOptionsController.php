<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductSpecificationOptionRequest;
use App\Models\ProductSpecificationInfo;
use App\Models\ProductSpecificationOption;
use Illuminate\Http\Request;

class ProductSpecificationOptionsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $specification_options = ProductSpecificationOption::all();
        return view('specification_option.list', compact('specification_options'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $specification_info = ProductSpecificationInfo::select('field_local_name')->groupBy('field_local_name')->get();
        return view('specification_option.add', compact('specification_info'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductSpecificationOptionRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        ProductSpecificationOption::create($request->all());

        return redirect()->route('specification_option.index')
            ->with('success', 'Specification info created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $specification_option = ProductSpecificationOption::findOrFail($id);
        $specification_info = ProductSpecificationInfo::select('field_local_name')->groupBy('field_local_name')->get();
        return view('specification_option.add', compact('specification_option','specification_info'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProductSpecificationOptionRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        ProductSpecificationOption::findOrFail($id)->update($request->all());

        return redirect()->route('specification_option.index')
            ->with('success', 'Specification option updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ProductSpecificationOption::findOrFail($id)->delete();
        return redirect()->route('specification_option.index')
            ->with('success', 'Specification option deleted successfully.');
    }
}
