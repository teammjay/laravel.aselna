<?php

namespace App\Http\Controllers;

use App\Models\DeviceSeries;
use Illuminate\Http\Request;

class DeviceSeriesController extends Controller
{

    public function index()
    {
        $device_series = DeviceSeries::all();
        return view('device_series.list', compact('device_series'));
    }


    public function create()
    {
        return view('device_series.add');
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required',
            'status' => 'required|in:on,off',
        ]);

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by, 'status' => $statusCode]);

        DeviceSeries::create($request->all());

        return redirect()->route('device_series.index')
            ->with('success', 'Device Series created successfully.');
    }


    public function edit($id)
    {
        $device_series = DeviceSeries::findOrFail($id);
        return view('device_series.add', compact('device_series'));
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string',
            'status' => 'required|in:on,off',
        ]);

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by, 'status' => $statusCode]);

        DeviceSeries::findOrFail($id)->update($request->all());

        return redirect()->route('device_series.index')
            ->with('success', 'Device Series updated successfully');
    }


    public function destroy($id)
    {
        DeviceSeries::findOrFail($id)->delete();

        return redirect()->route('device_series.index')
            ->with('success', 'Device Series deleted successfully');
    }
}
