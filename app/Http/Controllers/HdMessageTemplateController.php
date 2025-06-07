<?php

namespace App\Http\Controllers;

use App\Http\Requests\HdMessageTemplateRequest;
use App\Models\HdMessageTemplate;
use App\Models\StoreType;
use Illuminate\Http\Request;

class HdMessageTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hd_message_templates = HdMessageTemplate::with('storeType')->get();
        $store_types = StoreType::orderBy('name','asc')->get();
        return view('hd_message_template.list', compact('hd_message_templates','store_types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $store_types = StoreType::orderBy('name','asc')->get();
        return view('hd_message_template.add', compact('store_types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(HdMessageTemplateRequest $request)
    {
        $request->validated();

        $request['print_to_file'] = $request->has('print_to_file') ? 1 : 0;
        $request['replacement_order_shipped'] = $request->has('replacement_order_shipped') ? 1 : 0;

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);


        HdMessageTemplate::create($request->all());

        return redirect()->route('hd_message_template.index')
            ->with('success', 'HD Message Template created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $hd_message_template = HdMessageTemplate::findOrFail($id);
        $store_types = StoreType::orderBy('name','asc')->get();
        return view('hd_message_template.add', compact('hd_message_template','store_types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(HdMessageTemplateRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        $request['print_to_file'] = $request->has('print_to_file') ? 1 : 0;
        $request['replacement_order_shipped'] = $request->has('replacement_order_shipped') ? 1 : 0;

        HdMessageTemplate::findOrFail($id)->update($request->all());

        return redirect()->route('hd_message_template.index')
            ->with('success', 'HD Message Template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //HdMessageTemplate::findOrFail($id)->delete();

        return redirect()->route('hd_message_template.index')
            ->with('success', 'HD Message Template deleted successfully.');
    }
}
