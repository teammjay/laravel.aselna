<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShippingSolutionRequest;
use App\Models\ShippingSolution;
use Illuminate\Http\Request;

class ShippingSolutionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shipping_solutions = ShippingSolution::all();
        return view('shipping_solution.list', compact('shipping_solutions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('shipping_solution.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ShippingSolutionRequest $request)
    {
        $request->validated();

        $request['shipment_estimate_available'] = $request->has('shipment_estimate_available') ? 1 : 0;

        ShippingSolution::create($request->all());

        return redirect()->route('shipping_solution.index')
            ->with('success', 'Shipping package created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $shipping_solution = ShippingSolution::findOrFail($id);
        return view('shipping_solution.add', compact('shipping_solution'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ShippingSolutionRequest $request, string $id)
    {
        $request->validated();

        $request['shipment_estimate_available'] = $request->has('shipment_estimate_available') ? 1 : 0;

        ShippingSolution::findOrFail($id)->update($request->all());

        return redirect()->route('shipping_solution.index')
            ->with('success', 'Shipping package updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        ShippingSolution::findOrFail($id)->delete();

        return redirect()->route('shipping_solution.index')
            ->with('success', 'Shipping package deleted successfully.');
    }
}
