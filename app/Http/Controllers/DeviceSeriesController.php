<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceSeriesRequest;
use App\Models\Device;
use App\Models\DeviceSeries;
use App\Models\DevicesLinkedDevice;
use Illuminate\Support\Facades\Log;

class DeviceSeriesController extends Controller
{

    public function index()
    {
        $device_series = DeviceSeries::all();
        return view('device_series.list', compact('device_series'));
    }


    public function create()
    {
        $devices = Device::where('status', 'active')->get();
        return view('device_series.add', compact('devices'));
    }


    public function store(DeviceSeriesRequest $request)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $created_by = auth()->user()->name ?? 'admin';
//        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by, 'status' => $statusCode]);

        DeviceSeries::create($request->only(['name','slug', 'created_by', 'updated_by']));

        $deviceSeries = DeviceSeries::where('slug', $request->slug)->first();
        Log::info('Created Device Series: ' . $deviceSeries->id);

        // Save linked devices
        if ($request->has('devices')) {
            foreach ($request->devices as $deviceId) {
                DevicesLinkedDevice::create([
                    'device_id' => $deviceId,
                    'device_series_id' => $deviceSeries->id
                ]);
            }
        }

        return redirect()->route('device_series.index')
            ->with('success', 'Device Series created successfully.');
    }


    public function edit($id)
    {
        $device_series = DeviceSeries::findOrFail($id);
        $devices = Device::where('status', 'active')->get();
        $deviceLinks = DevicesLinkedDevice::where('device_series_id', $id)->pluck('device_id')->toArray();

        return view('device_series.add', compact('device_series', 'devices', 'deviceLinks'));
    }


    public function update(DeviceSeriesRequest $request, $id)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by, 'status' => $statusCode]);

        DeviceSeries::findOrFail($id)->update($request->all());

        // Remove all existing linked devices
        DevicesLinkedDevice::where('device_series_id', $id)->delete();

        // Add new linked devices
        if ($request->has('devices')) {
            foreach ($request->devices as $deviceId) {
                DevicesLinkedDevice::create([
                    'device_id' => $deviceId,
                    'device_series_id' => $id
                ]);
            }
        }

        return redirect()->route('device_series.index')
            ->with('success', 'Device Series updated successfully');
    }


    public function destroy($id)
    {
//        DeviceSeries::findOrFail($id)->delete();

        return redirect()->route('device_series.index')
            ->with('success', 'Device Series deleted successfully');
    }
}
