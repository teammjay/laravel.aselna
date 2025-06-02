<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderOperationReasonRequest;
use App\Models\OrderOperationReason;
use App\Models\StoreType;
use Illuminate\Http\Request;

class OrderOperationReasonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $order_operation_reasons = OrderOperationReason::with('storeType')->get();
        return view('order_operation_reason.list', compact('order_operation_reasons'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $store_types = StoreType::all();
        $order_operation_reasons = OrderOperationReason::all();
        return view('order_operation_reason.add',compact('store_types','order_operation_reasons'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OrderOperationReasonRequest $request)
    {
        $request->validated();

        OrderOperationReason::create($request->all());

        return redirect()->route('order_operation_reason.index')
            ->with('success', 'Order operation reason created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $order_operation_reason = OrderOperationReason::findOrFail($id);
        $store_types = StoreType::all();
        $order_operation_reasons = OrderOperationReason::all();
        return view('order_operation_reason.add', compact('order_operation_reason','store_types','order_operation_reasons'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(OrderOperationReasonRequest $request, string $id)
    {
        $request->validated();

        OrderOperationReason::findOrFail($id)->update($request->all());

        return redirect()->route('order_operation_reason.index')
            ->with('success', 'Order operation reason updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        OrderOperationReason::findOrFail($id)->delete();

        return redirect()->route('order_operation_reason.index')
            ->with('success', 'Order operation reason deleted successfully.');
    }
}
