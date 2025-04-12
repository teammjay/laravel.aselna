<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogisticCenterRequest;
use App\Models\LogisticCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LogisticCenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $logistic_centers = LogisticCenter::all();
        return view('logistic_center.list', compact('logistic_centers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('logistic_center.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LogisticCenterRequest $request)
    {
        $request->validated();

        $request['client_id'] = (string) Str::uuid();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by]);

        LogisticCenter::create($request->all());

        return redirect()->route('logistic_center.index')
            ->with('success', 'Shipping carrier created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $logistic_center = LogisticCenter::findOrFail($id);
        return view('logistic_center.add', compact('logistic_center'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LogisticCenterRequest $request, string $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        LogisticCenter::findOrFail($id)->update($request->all());

        return redirect()->route('logistic_center.index')
            ->with('success', 'Shipping carrier updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
//        LogisticCenter::findOrFail($id)->delete();

        return redirect()->route('logistic_center.index')
            ->with('success', 'Shipping carrier deleted successfully.');
    }
}
