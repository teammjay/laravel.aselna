<?php

namespace App\Http\Controllers;

use App\Helpers\QueryFilters;
use App\Http\Requests\DeviceRequest;
use App\Models\Brand;
use App\Models\Carrier;
use App\Models\Device;
use App\Models\DeviceCarrier;
use App\Models\DeviceInterface;
use App\Models\DeviceRating;
use App\Models\DeviceScreen;
use App\Models\DeviceSeries;
use App\Models\DeviceSubTypes;
use App\Models\DeviceTypes;
use App\Models\Interfaces;
use App\Services\DataTable\DataTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceController extends Controller
{

    public function index()
    {
        //need to get the brand relation name also in all the devices
        $devices = Device::with('brand')->get();
        return view('device.list', compact('devices'));
    }

    public function getDeviceDetails($id)
    {

        $device = Device::with(['deviceType', 'deviceSubType', 'brand', 'rating'])
            ->findOrFail($id);

        return response()->json([
            'device_type' => $device->deviceType,
            'device_sub_type' => $device->deviceSubType,
            'brand' => $device->brand,
            'name' => $device->name,
            'status' => $device->status,
            'rating' => $device->rating
        ]);
    }

    public function getDeviceCarriers($id)
    {
        $carriers = DeviceCarrier::with('carrier:id,name')->where('device_id',$id)->get();

        return response()->json($carriers);
    }

    public function getDeviceInterfaces($id)
    {
        $interfaces = DeviceInterface::with('interfaces:id,name,product_type_id','interfaces.ProductType')->where('device_id',$id)->get();

        return response()->json($interfaces);
    }

    public function getDeviceData(Request $request)
    {
        $totalRecords = Device::query()->count();
        $query = Device::query()->with('brand', 'deviceType');

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

        $dataTableService->setCustomSearch(function ($query, $request) {
            $query
                ->when($request->input('name'), function ($query) use ($request) {
                    QueryFilters::applyFilter($query, $request, 'name', 'name_operator', 'name');
                })
                ->when($request->input('brand_name'), function ($query) use ($request) {
                    $query->whereHas('brand', function ($q) use ($request) {
                        QueryFilters::applyFilter($q, $request, 'name', 'brand_name_operator', 'brand_name');
                    });
                })
                ->when($request->input('device_type'), function ($query) use ($request) {
                    $query->whereHas('deviceType', function ($q) use ($request) {
                        QueryFilters::applyFilter($q, $request, 'name', 'device_type_operator', 'device_type');
                    });
                })
                ->when($request->input('status'), function ($query) use ($request) {
                    QueryFilters::applyFilter($query, $request, 'status', 'status_operator', 'status');
                })
                ->when($request->input('created_at'), function ($query) use ($request) {
                    QueryFilters::applyDateFilter($query,$request, 'created_at', $request->input('created_at_operator'), $request->input('created_at'));
                });
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
        $carriers = Carrier::all();
        $interfaces = Interfaces::all();
        $deviceCarriers = [];
        $deviceInterfaces = [];


        return view('device.add', compact('deviceTypes', 'deviceSubTypes', 'deviceSeries', 'deviceRatings', 'deviceScreens', 'brands', 'carriers', 'interfaces','deviceCarriers','deviceInterfaces'));
    }

    public function store(DeviceRequest $request)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';;

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by, 'updated_by' => $created_by, 'status' => $statusCode]);

        $carriers = $request->carriers;
        $interfaces = $request->interfaces;
        unset($request['carriers'],$request['interfaces']);

        $device = Device::create($request->all());

        // Sync carriers
        if($carriers) {
            $syncCarrierData = [];
            foreach ($carriers as $carrierId) {
                $syncCarrierData[$carrierId] = ['id' => (string)Str::uuid()];
            }
            $device->devicecarriers()->sync($syncCarrierData ?? []);
        }

        // Sync interfaces
        if($interfaces) {
            $syncInterfaceData = [];
            foreach ($interfaces as $interfaceId) {
                $syncInterfaceData[$interfaceId] = ['id' => (string)Str::uuid()];
            }
            $device->deviceinterfaces()->sync($syncInterfaceData ?? []);
        }

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
        $carriers = Carrier::all();
        $interfaces = Interfaces::with('ProductType')->orderBy('name','asc')->get();
        $deviceCarrier = DeviceCarrier::where('device_id',$id)->get();
        $deviceCarriers = $deviceCarrier->pluck('carrier_id')->values()->all();
        $deviceInterface = DeviceInterface::where('device_id',$id)->get();
        $deviceInterfaces = $deviceInterface->pluck('interface_id')->values()->all();

        return view('device.add', compact('device', 'deviceTypes', 'deviceSubTypes', 'deviceSeries', 'deviceRatings', 'deviceScreens', 'brands', 'carriers', 'interfaces','deviceCarriers','deviceInterfaces'));
    }

    public function update(DeviceRequest $request, $id)
    {
        $request->validated();

        $status = $request->status ?? 'off';
        $statusCode = $status === 'on' ? 'active' : 'inactive';

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by, 'status' => $statusCode]);

        $request['release_date'] = date('Y-m-d',strtotime($request->release_date));

        $device = Device::findOrFail($id);

        $carriers = $request->carriers;
        $interfaces = $request->interfaces;

        unset($request['carriers'],$request['interfaces']);

        $device->update($request->all());

        // Sync carriers
        if($carriers) {
            $syncCarrierData = [];
            foreach ($carriers as $carrierId) {
                $syncCarrierData[$carrierId] = ['id' => (string)Str::uuid()];
            }
            $device->devicecarriers()->sync($syncCarrierData ?? []);
        }

        // Sync interfaces
        if($interfaces) {
            $syncInterfaceData = [];
            foreach ($interfaces as $interfaceId) {
                $syncInterfaceData[$interfaceId] = ['id' => (string)Str::uuid()];
            }
            $device->deviceinterfaces()->sync($syncInterfaceData ?? []);
        }

        return redirect()->route('devices.index')
            ->with('success', 'Device updated successfully.');
    }

    public function destroy($id)
    {
        // Device::findOrFail($id)->delete();

        return redirect()->route('devices.index')
            ->with('success', 'Device deleted successfully.');
    }

}
