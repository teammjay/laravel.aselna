<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrinterClientsRequest;
use App\Models\PrinterClient;
use Illuminate\Http\Request;

class PrinterClientsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $printer_clients = PrinterClient::all();
        return view('printer_client.list', compact('printer_clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('printer_client.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PrinterClientsRequest $request)
    {
        $request->validated();

        $request['active'] = $request->has('active') ? 1 : 0;

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        PrinterClient::create($request->all());

        return redirect()->route('printer_client.index')
            ->with('success', 'Printer client created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $printer_client = PrinterClient::findOrFail($id);
        return view('printer_client.add', compact('printer_client'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PrinterClientsRequest $request, string $id)
    {
        $request->validated();

        $request['active'] = $request->has('active') ? 1 : 0;

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        PrinterClient::findOrFail($id)->update($request->all());

        return redirect()->route('printer_client.index')
            ->with('success', 'Printer client updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        Printer::findOrFail($id)->delete();

        return redirect()->route('printer_client.index')
            ->with('success', 'Printer client deleted successfully.');
    }
}
