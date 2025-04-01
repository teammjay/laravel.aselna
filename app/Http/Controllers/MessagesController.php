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

        // Filter by ticket email
        if ($request->has('sender_email') && $request->input('sender_email') != '') {
            $query->where('ticket_email', $request->input('sender_email'));
        }

        // Filter by ticket type
        if ($request->has('ticket_type') && $request->input('ticket_type') != '') {
            $query->where('ticket_type_id', $request->input('ticket_type'));
        }

        // Filter by ticket code
        if ($request->has('ticket_code') && $request->input('ticket_code') != '') {
            $query->where('ticket_code', $request->input('ticket_code'));
        }

        // Filter by order id
        if ($request->has('order_id') && $request->input('order_id') != '') {
            $query->where('order_id', $request->input('order_id'));
        }

        // Filter by subject
        if ($request->has('subject') && $request->input('subject') != '') {
            $query->where('subject', $request->input('subject'));
        }

        // Filter by notes
        if ($request->has('body') && $request->input('body') != '') {
            $query->where('notes', $request->input('body'));
        }

        // Filter by notes
        if ($request->has('user_id') && $request->input('user_id') != '') {
            $query->where('customer_id', $request->input('user_id'));
        }

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
        return view('messages.update');
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
        return view('messages.update', compact('message','messages'));
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
//        Message::findOrFail($id)->delete();

        return redirect()->route('message.index')
            ->with('success', 'Message deleted successfully.');
    }
}
