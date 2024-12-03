<?php

namespace App\Http\Controllers;

use App\Models\ListingGenerationType;
use App\Models\ListingGenerationTypeTemplate;
use App\Http\Requests\ListingGenerationTypeTemplateRequest;
use Illuminate\Http\Request;

class ListingGenerationTypeTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $listing_generation_type_templates = ListingGenerationTypeTemplate::all();

        return view('listing_generation_type_template.list', compact('listing_generation_type_templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $listing_generation_type_templates = ListingGenerationTypeTemplate::all();
        $listing_generation_types = ListingGenerationType::all();
        return view('listing_generation_type_template.add', compact('listing_generation_type_templates','listing_generation_types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ListingGenerationTypeTemplateRequest $request)
    {
        $request->validated();

        $request['default_template'] = $request->has('default_template') ? 1 : 0;

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        ListingGenerationTypeTemplate::create($request->all());

        return redirect()->route('listing_generation_type_template.index')
            ->with('success', 'Listing Generation Type created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $listing_generation_type_template = ListingGenerationTypeTemplate::findOrFail($id);
        $listing_generation_types = ListingGenerationType::all();
        return view('listing_generation_type_template.add', compact('listing_generation_type_template','listing_generation_types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ListingGenerationTypeTemplateRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        ListingGenerationTypeTemplate::findOrFail($id)->update($request->all());

        return redirect()->route('listing_generation_type_template.index')
            ->with('success', 'Listing Generation Type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ListingGenerationTypeTemplate::findOrFail($id)->delete();

        return redirect()->route('listing_generation_type_template.index')
            ->with('success', 'Listing Generation Type deleted successfully.');
    }
}
