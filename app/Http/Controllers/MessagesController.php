<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Store;
use Illuminate\Http\Request;
use App\Services\DataTable\DataTableService;

class MessagesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stores = Store::orderBy('name','asc')->get();
        return view('messages.list', compact('stores'));
    }

    public function getMessageData(Request $request)
    {
        $totalRecords = Message::query()->count();
        $query = Message::query();

        $dataTableService = new DataTableService($query, $request);

        $dataTableService->setCustomSorting(function ($query, $request) {
            $columnIndex = $request->input('order.0.column', 0);
            $columnName = $request->input('columns.' . $columnIndex . '.name', 'id');
            $sortDirection = $request->input('order.0.dir', 'asc');
            $query->orderBy($columnName, $sortDirection);
        });

        $data = $dataTableService->handle();

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $data->total(),
            'data' => $data->items(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('message.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        Message::create($request->all());

        return redirect()->route('message.index')
            ->with('success', 'Message created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $message = Message::findOrFail($id);
        $messages = Message::all();
        return view('message.add', compact('message','messages'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        Message::findOrFail($id)->update($request->all());

        return redirect()->route('message.index')
            ->with('success', 'Message updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Message::findOrFail($id)->delete();

        return redirect()->route('message.index')
            ->with('success', 'Message deleted successfully.');
    }
}
