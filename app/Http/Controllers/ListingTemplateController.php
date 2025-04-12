<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListingTemplateRequest;
use App\Models\ListingTemplate;
use Illuminate\Http\Request;

class ListingTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $listing_templates = ListingTemplate::all();

        return view('listing_template.list', compact('listing_templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $listing_templates = ListingTemplate::all();
        return view('listing_template.add', compact('listing_templates'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ListingTemplateRequest $request)
    {
        $request->validated();

        $request['default_template'] = $request->has('default_template') ? 1 : 0;

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        ListingTemplate::create($request->all());

        return redirect()->route('listing_template.index')
            ->with('success', 'Listing Template created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $listing_template = ListingTemplate::findOrFail($id);
        return view('listing_template.add', compact('listing_template'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ListingTemplateRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        ListingTemplate::findOrFail($id)->update($request->all());

        return redirect()->route('listing_template.index')
            ->with('success', 'Listing Template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        ListingTemplate::findOrFail($id)->delete();

        return redirect()->route('listing_template.index')
            ->with('success', 'Listing Template deleted successfully.');
    }
}
