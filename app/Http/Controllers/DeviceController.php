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
use App\Services\DataTable\DataTableService;
use Illuminate\Http\Request;

class DeviceController extends Controller
{

    public function index()
    {
        //need to get the brand relation name also in all the devices
        $devices = Device::with('brand')->get();
        return view('device.list', compact('devices'));
    }

    public function getDeviceData(Request $request)
    {
        $totalRecords = Device::query()->count();
        $query = Device::query()->with('brand','deviceType');

        $dataTableService = new DataTableService($query, $request);

        $dataTableService->setCustomSorting(function ($query, $request) {
            $columnIndex = $request->input('order.0.column', 0);
            $columnName = $request->input('columns.' . $columnIndex . '.name', 'id');
            $sortDirection = $request->input('order.0.dir', 'asc');
            if ($columnName == 'brand_name') {
                $query->join('brand', 'brand.id', '=', 'device.brand_id')
                    ->orderBy('brand.name', $sortDirection);
            } elseif ($columnName == 'device_type_name') {
                $query->join('device_type', 'device_type.id', '=', 'device.device_type_id')
                    ->orderBy('device_type.name', $sortDirection);
            } else {
                $query->orderBy($columnName, $sortDirection);
            }
        });

        $data = $dataTableService->handle();

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $data->total(),
            'data' => $data->items(),
        ]);
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
