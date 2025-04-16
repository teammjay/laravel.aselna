<?php

namespace App\Http\Controllers;

use App\Helpers\QueryFilters;
use App\Models\DeviceInterface;
use App\Services\DataTable\DataTableService;
use Illuminate\Http\Request;

class DeviceInterfaceController extends Controller
{
    public function index()
    {
        return view('device_interface.list');
    }

    public function getDeviceInterfaceData(Request $request)
    {
        $totalRecords = DeviceInterface::query()->count();
        $query = DeviceInterface::query()->with('device', 'interfaces');

        $dataTableService = new DataTableService($query, $request);

        $dataTableService->setCustomSorting(function ($query, $request) {
            $columnIndex = $request->input('order.0.column', 0);
            $columnName = $request->input('columns.' . $columnIndex . '.name', 'id');
            $sortDirection = $request->input('order.0.dir', 'asc');
            if ($columnName == 'device_name') {
                $query->join('device', 'device.id', '=', 'device_interface.device_id')
                    ->orderBy('device.name', $sortDirection);
            } elseif ($columnName == 'interface_name') {
                $query->join('interface', 'interface.id', '=', 'device_interface.interface_id')
                    ->orderBy('interface.name', $sortDirection);
            }
        });

        $dataTableService->setCustomSearch(function ($query, $request) {
            $query
                ->when($request->input('device_name'), function ($query) use ($request) {
                    $query->whereHas('device', function ($q) use ($request) {
                        QueryFilters::applyFilter($q, $request, 'name', 'device_name_operator', 'device_name');
                    });
                })
                ->when($request->input('interface_name'), function ($query) use ($request) {
                    $query->whereHas('interfaces', function ($q) use ($request) {
                        QueryFilters::applyFilter($q, $request, 'name', 'interface_name_operator', 'interface_name');
                    });
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
}
