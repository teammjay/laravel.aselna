<?php

namespace App\Http\Controllers;

use App\Constants\ShippingDocumentType;
use App\Models\LogisticCenter;
use App\Models\Printer;
use App\Models\ShipmentDocumentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LogisticCenterDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $documents = ShipmentDocumentSetting::with('logisticCenter')->get();
        return view('logistic_center_document.list', compact('documents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $logistic_centers = LogisticCenter::all();
        $printers = Printer::all();
        $document_types = ShippingDocumentType::names;
        return view('logistic_center_document.add', compact('logistic_centers', 'document_types','printers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client' => 'required',
            'enabled' => 'required|boolean',
            'document_type' => 'required|integer',
            'printer_id' => 'required|string',
            'shipping_from_location_id' => 'required|string',
            'page_size_id' => 'required|string',
            'two_per_page' => 'required|boolean',
            'pdf_action' => 'nullable|integer',
            'rotate' => 'required|integer',
        ]);

        $validated['id'] = (string) Str::uuid();

        $created_by = auth()->user()->name ?? 'admin';
        $validated['created_by'] = $created_by;
        $validated['updated_by'] = $created_by;

        ShipmentDocumentSetting::create($validated);

        return redirect()->route('logistic_center_document.index')
            ->with('success', 'Document setting created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $document = ShipmentDocumentSetting::findOrFail($id);
        $logistic_centers = LogisticCenter::all();
        $document_types = ShippingDocumentType::names;
        $printers = Printer::all();
        return view('logistic_center_document.add', compact('document', 'logistic_centers', 'document_types','printers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'client' => 'required',
            'enabled' => 'required|boolean',
            'document_type' => 'required|integer',
            'printer_id' => 'required|string',
            'shipping_from_location_id' => 'required|string',
            'page_size_id' => 'required|string',
            'two_per_page' => 'required|boolean',
            'pdf_action' => 'nullable|integer',
            'rotate' => 'required|integer',
        ]);

        $updated_by = auth()->user()->name ?? 'admin';
        $validated['updated_by'] = $updated_by;

        ShipmentDocumentSetting::findOrFail($id)->update($validated);

        return redirect()->route('logistic_center_document.index')
            ->with('success', 'Document setting updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ShipmentDocumentSetting::findOrFail($id)->delete();

        return redirect()->route('logistic_center_document.index')
            ->with('success', 'Document setting deleted successfully.');
    }
}
