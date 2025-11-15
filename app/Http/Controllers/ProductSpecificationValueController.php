<?php

namespace App\Http\Controllers;

use App\Models\ProductSpecifcationUniqueInfo;
use App\Models\ProductSpecificationInfo;
use App\Models\ProductSpecificationValue;
use App\Models\StoreType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductSpecificationValueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $specification_infos = ProductSpecifcationUniqueInfo::all();
        return view('specification_values.list', compact('specification_infos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $specification_infos = ProductSpecificationInfo::select('field_name')->distinct()->get();
        return view('specification_values.add', compact('specification_infos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'field_name' => 'required|unique:product_specification_unique_info',
            'field_value' => 'required|array',
        ]);

        $created_by = auth()->user()->name ?? 'admin';

        $specificationInfo = ProductSpecifcationUniqueInfo::create([
            'field_name' => $request->get('field_name'),
            'created_by' => $created_by,
            'updated_by' => $created_by
        ]);

        foreach ($request->get('field_value') as $key => $value) {
            if (isset($value)) {
                ProductSpecificationValue::create([
                    'product_specification_id' => $specificationInfo->id,
                    'field_value' => $value,
                    'created_by' => $created_by,
                    'updated_by' => $created_by
                ]);
            }
        }


        return redirect()->route('specification_values.index')
            ->with('success', 'Specification value created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $specification_info = ProductSpecifcationUniqueInfo::findOrFail($id);
        $specification_infos = ProductSpecificationInfo::select('field_name')->distinct()->get();
        //$specification_values = ProductSpecificationValue::where('product_specification_id', $id)->get();
        $storeTypes = StoreType::orderBy('name')->get();
        $categories = ProductSpecificationValue::select('category')->orderBy('category')->groupBy('category')->get();

        return view('specification_values.add', compact( 'specification_infos', 'specification_info','storeTypes','categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'field_name' => 'required|exists:product_specification_unique_info,id',
            'field_value' => 'required|array',
        ]);

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        foreach ($request->get('field_value') as $key => $value) {
            if (!isset($value)) continue;
            if (uuid_is_valid($key) && ProductSpecificationValue::where('id', $key)->exists()) {
                $specificationValue = ProductSpecificationValue::where('id', $key)->first();
                Log::info($specificationValue);
                Log::info($value);
                $specificationValue->update([
                    'field_value' => $value,
                    'updated_by' => $updated_by
                ]);
            } else {
                ProductSpecificationValue::create([
                    'product_specification_id' => $request->get('field_name'),
                    'field_value' => $value,
                    'store_type_id' => $request->input('store_type_id') ?? NULL,
                    'category' => $request->input('category') ?? NULL,
                    'created_by' => $updated_by,
                    'updated_by' => $updated_by
                ]);
            }
        }

        return redirect()->route('specification_values.index')
            ->with('success', 'Specification value updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ProductSpecifcationUniqueInfo::findOrFail($id)->delete();

        return redirect()->route('specification_values.index')
            ->with('success', 'Specification value deleted successfully.');
    }

    /**
     * Remove specification value
     */
    public function removeValue(Request $request)
    {
        $id = $request->input('id');
        ProductSpecificationValue::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Data saved successfully!']);
    }

    public function getSpecValue(Request $request){
        $id = $request->input('id');

        $query = ProductSpecificationValue::with('storeType')->where('product_specification_id', $id);
        if ($request->has('store_type_id') && $request->input('store_type_id') != '') {
            $query->where('store_type_id', $request->input('store_type_id'));
        }

        if ($request->has('category') && $request->input('category') != '') {
            $query->where('category', $request->input('category'));
        }

        $specification_values = $query->get();

        if($specification_values) {
            $html = view('specification_values.spec_value', compact('specification_values'))->render();

            return response()->json([
                'success'=> true,
                'html' => $html,
            ]);
        }

        return response()->json([
            'success'=> false
        ]);
    }
}
