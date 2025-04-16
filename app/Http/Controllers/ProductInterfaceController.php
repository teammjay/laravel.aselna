<?php

namespace App\Http\Controllers;

use App\Helpers\QueryFilters;
use App\Models\ProductInterface;
use App\Services\DataTable\DataTableService;
use Illuminate\Http\Request;

class ProductInterfaceController extends Controller
{

    public function index()
    {
        $productInterfaces = ProductInterface::all();
        return view('product_interface.list', compact('productInterfaces'));
    }

    public function getProductInterfaceData(Request $request)
    {
        $totalRecords = ProductInterface::query()->count();
        $query = ProductInterface::query()->with('product', 'interfaces');

        $dataTableService = new DataTableService($query, $request);

        $dataTableService->setCustomSorting(function ($query, $request) {
            $columnIndex = $request->input('order.0.column', 0);
            $columnName = $request->input('columns.' . $columnIndex . '.name', 'id');
            $sortDirection = $request->input('order.0.dir', 'asc');
            if ($columnName == 'product_name') {
                $query->join('products', 'products.product_id', '=', 'product_interface.product_id')
                    ->orderBy('products.product_title', $sortDirection);
            } elseif ($columnName == 'interface_name') {
                $query->join('interface', 'interface.id', '=', 'product_interface.interface_id')
                    ->orderBy('interface.name', $sortDirection);
            }
        });

        $dataTableService->setCustomSearch(function ($query, $request) {
            $query
                ->when($request->input('product_name'), function ($query) use ($request) {
                    $query->whereHas('product', function ($q) use ($request) {
                        QueryFilters::applyFilter($q, $request, 'product_title', 'product_name_operator', 'product_name');
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
