<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogisticCenterRequest;
use App\Models\LogisticCenter;
use App\Models\ShipmentDocumentSetting;
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

        $request['client_id'] = (string)Str::uuid();

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
        $fromAddress = $logistic_center->fromAddress;
        $returnAddress = $logistic_center->returnAddress;
        return view('logistic_center.add', compact('logistic_center', 'fromAddress', 'returnAddress'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tabId = $request->input('tab');
        $logicticCenter = LogisticCenter::findOrFail($id);

        if ($tabId == 1) {

            $validated = $request->validate([
                'name' => 'required',
                ''
            ]);

            $updated_by = auth()->user()->name ?? 'admin';
            $validated['updated_by'] = $updated_by;

            $logicticCenter->update($validated);

            return redirect()->route('logistic_center_document.index')
                ->with('success', 'Document setting updated successfully.');
        } elseif ($tabId == 2) {
            $fromAddress = $logicticCenter->fromAddress;
            // Handle from address update logic here
            $validated = $request->validate([
                'first_name',
                'last_name',
                'company',
                'address_line_1',
                'address_line_2',
                'address_line_3',
                'phone',
                'email',
                'city',
                'state_or_region',
                'postal_code',
                'country',
                'country_code',
                'district',
                'client',
            ]);
            $fromAddress->update($validated);

            return redirect()->route('logistic_center.edit', ['id' => $id])
                ->with('success', 'From address updated successfully.');
        } elseif ($tabId == 3) {
            $returnAddress = $logicticCenter->returnAddress;
            // Handle other tab updates if necessary
            $returnAddress->update($request->only([
                'first_name',
                'last_name',
                'company',
                'address_line_1',
                'address_line_2',
                'address_line_3',
                'phone',
                'email',
                'city',
                'state_or_region',
                'postal_code',
                'country',
                'country_code',
                'district',
                'client',
            ]));

            return redirect()->route('logistic_center.edit', ['id' => $id])
                ->with('success', 'Return address updated successfully.');
        } else {
            return redirect()->route('logistic_center.edit', ['id' => $id])
                ->with('error', 'Invalid tab selection.');
        }
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
