<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeviceRequest;
use App\Models\Brand;
use App\Models\Device;
use App\Models\DeviceRating;
use App\Models\DeviceScreen;
use App\Models\DeviceSeries;
use App\Models\DeviceSubTypes;
use App\Models\DeviceTypes;
use Illuminate\Http\Request;

class DeviceController extends Controller
{

    public function index()
    {
        $devices = Device::all();
        return view('device.list', compact('devices'));
    }

    public function create()
    {
        // Get All Device Types, Device Sub Types, Device Series, Device Ratings, Device Screens, Brands for select dropdown
        $deviceTypes = DeviceTypes::all();
        $deviceSubTypes = DeviceSubTypes::all();
        $deviceSeries = DeviceSeries::all();
        $deviceRatings = DeviceRating::all();
        $deviceScreens = DeviceScreen::all();
        $brands = Brand::all();

        return view('device.add', compact('deviceTypes', 'deviceSubTypes', 'deviceSeries', 'deviceRatings', 'deviceScreens', 'brands'));
    }

    public function store(DeviceRequest $request)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';;

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by, 'status' => $statusCode]);

        Device::create($request->all());

        return redirect()->route('devices.index')
            ->with('success', 'Device created successfully.');
    }

    public function edit($id)
    {
        $device = Device::findOrFail($id);
        $deviceTypes = DeviceTypes::all();
        $deviceSubTypes = DeviceSubTypes::all();
        $deviceSeries = DeviceSeries::all();
        $deviceRatings = DeviceRating::all();
        $deviceScreens = DeviceScreen::all();
        $brands = Brand::all();
        return view('device.add', compact('device', 'deviceTypes', 'deviceSubTypes', 'deviceSeries', 'deviceRatings', 'deviceScreens', 'brands'));
    }

    public function update(DeviceRequest $request, $id)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by, 'status' => $statusCode]);

        Device::findOrFail($id)->update($request->all());

        return redirect()->route('devices.index')
            ->with('success', 'Device updated successfully.');
    }

    public function destroy($id)
    {
        Device::findOrFail($id)->delete();

        return redirect()->route('devices.index')
            ->with('success', 'Device deleted successfully.');
    }

}
