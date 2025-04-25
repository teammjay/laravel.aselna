<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrintersRequest;
use App\Models\Printer;
use App\Models\PrinterClient;
use Illuminate\Http\Request;

class PrintersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $printers = Printer::with('PrinterClient')->get();
        return view('printer.list', compact('printers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $printer_clients = PrinterClient::all();
        return view('printer.add',compact('printer_clients'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PrintersRequest $request)
    {
        $request->validated();

        $request['status'] = $request->has('status') ? 1 : 0;

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        Printer::create($request->all());

        return redirect()->route('printer.index')
            ->with('success', 'Printer created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $printer = Printer::findOrFail($id);
        $printer_clients = PrinterClient::all();
        return view('printer.add', compact('printer','printer_clients'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PrintersRequest $request, string $id)
    {
        $request->validated();

        $request['status'] = $request->has('status') ? 1 : 0;

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        Printer::findOrFail($id)->update($request->all());

        return redirect()->route('printer.index')
            ->with('success', 'Printer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        Printer::findOrFail($id)->delete();

        return redirect()->route('printer.index')
            ->with('success', 'Printer deleted successfully.');
    }
}
