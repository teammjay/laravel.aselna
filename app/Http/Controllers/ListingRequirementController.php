<?php

namespace App\Http\Controllers;

use App\Models\ListingRequirement;
use Illuminate\Http\Request;

class ListingRequirementController extends Controller
{
    public function index()
    {
        $requirements = ListingRequirement::all();
        return view('listing_requirements.index', compact('requirements'));
    }

    public function create()
    {
        return view('listing_requirements.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => 'required|string',
            'type' => 'required|in:column,key_value',
            'table' => 'required|string',
            'column' => 'nullable|string',
            'key_column' => 'nullable|string',
            'key_value' => 'nullable|string',
            'value_column' => 'nullable|string',
        ]);

        ListingRequirement::create($data);
        return redirect()->route('listing_requirements.index')->with('success', 'Requirement added.');
    }

    public function edit(ListingRequirement $listingRequirement)
    {
        return view('listing_requirements.edit', compact('listingRequirement'));
    }

    public function update(Request $request, ListingRequirement $listingRequirement)
    {
        $data = $request->validate([
            'category' => 'required|string',
            'type' => 'required|in:column,key_value',
            'table' => 'required|string',
            'column' => 'nullable|string',
            'key_column' => 'nullable|string',
            'key_value' => 'nullable|string',
            'value_column' => 'nullable|string',
        ]);

        $listingRequirement->update($data);
        return redirect()->route('listing_requirements.index')->with('success', 'Requirement updated.');
    }

    public function destroy(ListingRequirement $listingRequirement)
    {
        $listingRequirement->delete();
        return redirect()->route('listing_requirements.index')->with('success', 'Requirement deleted.');
    }
}
