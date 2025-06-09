<?php

namespace App\Http\Controllers;

use App\Http\Requests\HdTicketLabelRequest;
use App\Models\HdTicketLabel;
use Illuminate\Http\Request;

class HdTicketLabelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hd_ticket_labels = HdTicketLabel::all();
        return view('hd_ticket_label.list', compact('hd_ticket_labels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $ticket_labels = HdTicketLabel::all();
        return view('hd_ticket_label.add',compact('ticket_labels'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(HdTicketLabelRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);


        HdTicketLabel::create($request->all());

        return redirect()->route('hd_ticket_label.index')
            ->with('success', 'HD Ticket Label created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $hd_ticket_label = HdTicketLabel::findOrFail($id);
        $ticket_labels = HdTicketLabel::all();
        return view('hd_ticket_label.add', compact('hd_ticket_label','ticket_labels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(HdTicketLabelRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        HdTicketLabel::findOrFail($id)->update($request->all());

        return redirect()->route('hd_ticket_label.index')
            ->with('success', 'HD Ticket Label updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //HdTicketLabel::findOrFail($id)->delete();

        return redirect()->route('hd_ticket_label.index')
            ->with('success', 'HD Ticket Label deleted successfully.');
    }
}
