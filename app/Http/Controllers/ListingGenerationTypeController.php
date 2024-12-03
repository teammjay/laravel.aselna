<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingGenerationTypeRequest;
use App\Models\ListingGenerationType;
use Illuminate\Http\Request;

class ListingGenerationTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $listing_generation_types = ListingGenerationType::all();

        return view('listing_generation_type.list', compact('listing_generation_types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $listing_generation_types = ListingGenerationType::all();
        return view('listing_generation_type.add', compact('listing_generation_types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ListingGenerationTypeRequest $request)
    {
        $request->validated();

        $request['singleton'] = $request->has('singleton') ? 1 : 0;
        $request['allow_duplicates'] = $request->has('allow_duplicates') ? 1 : 0;

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        ListingGenerationType::create($request->all());

        return redirect()->route('listing_generation_type.index')
            ->with('success', 'Listing Generation Type created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $listing_generation_type = ListingGenerationType::findOrFail($id);
        $listing_generation_types = ListingGenerationType::all();
        return view('listing_generation_type.add', compact('listing_generation_type','listing_generation_types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ListingGenerationTypeRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        ListingGenerationType::findOrFail($id)->update($request->all());

        return redirect()->route('listing_generation_type.index')
            ->with('success', 'Listing Generation Type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ListingGenerationType::findOrFail($id)->delete();

        return redirect()->route('listing_generation_type.index')
            ->with('success', 'Listing Generation Type deleted successfully.');
    }
}
