<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManufacturerRequest;
use App\Models\Manufacturer;
use Illuminate\Http\Request;

class ManufacturerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $manufacturers = Manufacturer::all();
        return view('manufacturer.list', compact('manufacturers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('manufacturer.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ManufacturerRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by, 'version' => 1]);

        Manufacturer::create($request->all());

        return redirect()->route('manufacturer.index')
            ->with('success', 'Manufacturer created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $manufacturer = Manufacturer::findOrFail($id);
        return view('manufacturer.add', compact('manufacturer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ManufacturerRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        Manufacturer::findOrFail($id)->update($request->all());

        return redirect()->route('manufacturer.index')
            ->with('success', 'Manufacturer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Manufacturer::findOrFail($id)->delete();

        return redirect()->route('manufacturer.index')
            ->with('success', 'Manufacturer deleted successfully.');
    }
}
