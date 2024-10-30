<?php

namespace App\Http\Controllers;

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

    public function store(Request $request)
    {
        $request->validate([
            'dimensions_height' => 'nullable|string|max:25',
            'dimensions_width' => 'nullable|string|max:25',
            'name' => 'required',
            'release_date' => 'nullable|string|max:25',
            'image' => 'nullable|string|max:255',
            'thumbnail_image' => 'nullable|string|max:255',
            'notes' => 'required',
            'order' => 'required',
            'code' => 'required',
            'dimensions_length' => 'nullable|string|max:25',
            'dimensions_length_unit' => 'nullable|string|max:25',
            'device_sub_type_id' => 'nullable',
            'device_series_id' => 'nullable',
            'rating_id' => 'nullable',
            'screen_id' => 'nullable',
            'brand_id' => 'nullable',
            'device_type_id' => 'nullable',
            'status' => 'required|in:on,off',
        ]);

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

    public function update(Request $request, $id)
    {
        $request->validate([
            'dimensions_height' => 'nullable|string|max:25',
            'dimensions_width' => 'nullable|string|max:25',
            'name' => 'required',
            'release_date' => 'required',
//            'screen_size_unit' => 'required',
            'image' => 'nullable|string|max:255',
            'thumbnail_image' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'order' => 'required',
            'code' => 'required',
            'dimensions_length' => 'nullable|string|max:25',
            'dimensions_length_unit' => 'nullable|string|max:25',
            'device_sub_type_id' => 'nullable',
            'device_series_id' => 'nullable',
            'rating_id' => 'nullable',
            'screen_id' => 'nullable',
            'brand_id' => 'nullable',
            'device_type_id' => 'nullable',
            'status' => 'required|in:on,off',
        ]);

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
