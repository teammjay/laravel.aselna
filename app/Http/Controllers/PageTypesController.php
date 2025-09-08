<?php

namespace App\Http\Controllers;

use App\Http\Requests\PageTypeRequest;
use Illuminate\Http\Request;
use App\Models\PageType;

class PageTypesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $page_types = PageType::all();
        return view('page_type.list', compact('page_types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('page_type.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PageTypeRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        PageType::create($request->all());

        return redirect()->route('page_type.index')
            ->with('success', 'Page type created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $page_type = PageType::findOrFail($id);
        return view('page_type.add', compact('page_type'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PageTypeRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        PageType::findOrFail($id)->update($request->all());

        return redirect()->route('page_type.index')
            ->with('success', 'Page type updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        PageType::findOrFail($id)->delete();

        return redirect()->route('page_type.index')
            ->with('success', 'Page type deleted successfully.');
    }
}
