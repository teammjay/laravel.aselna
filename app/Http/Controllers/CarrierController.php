<?php

namespace App\Http\Controllers;

use App\Http\Requests\CarrierRequest;
use App\Models\Carrier;
use Illuminate\Http\Request;

class CarrierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $carriers = Carrier::all();

        return view('carrier.list', compact('carriers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('carrier.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CarrierRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        Carrier::create($request->all());

        return redirect()->route('carrier.index')
            ->with('success', 'Carrier created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $carrier = Carrier::findOrFail($id);
        $carrier->imageUrl = $carrier->getImage();
        $carrier->thumbnailImageUrl = $carrier->getThumbnailImage();
        return view('carrier.add', compact('carrier'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CarrierRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        Carrier::findOrFail($id)->update($request->all());

        return redirect()->route('carrier.index')
            ->with('success', 'Carrier updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        Carrier::findOrFail($id)->delete();

        return redirect()->route('carrier.index')
            ->with('success', 'Carrier deleted successfully.');
    }
}
