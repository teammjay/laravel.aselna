<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        // get the data in the pagination format for datatable to display
        $brands = Brand::all();
        return view('brand.list', compact('brands'));
    }

    public function data()
    {

    }

    public function create()
    {
        return view('brand.add');
    }

    public function store(BrandRequest $request)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'status' => $statusCode]);

        try {
            // save the data to the database
            Brand::create($request->all());
            // redirect to the brands list with success message
            return redirect()->route('brand.list')->with('success', 'Brand created successfully.');
        } catch (\Exception $e) {
            // handle any errors that may occur
            return redirect()->back()->withErrors(['error' => 'An error occurred while creating the brand. Please try again.']);
        }
    }

    public function edit($id)
    {
        $brand = Brand::findOrFail($id);
        $brand->imageUrl = $brand->getImage();
        $brand->thumbnailImageUrl = $brand->getThumbnailImage();
        return view('brand.add', compact('brand'));
    }

    public function update(BrandRequest $request, $id)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by, 'status' => $statusCode]);

        // save the data to the database
        Brand::findOrFail($id)->update($request->all());

        // redirect to the brands list
        return redirect()->route('brand.list');
    }

    public function destroy($id)
    {
        // delete the brand
        Brand::findOrFail($id)->delete();

        // redirect to the brands list
        return redirect()->route('brand.list');
    }
}
