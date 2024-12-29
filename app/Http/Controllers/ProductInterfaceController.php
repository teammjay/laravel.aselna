<?php

namespace App\Http\Controllers;

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

        $data = $dataTableService->handle();

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $data->total(),
            'data' => $data->items(),
        ]);
    }
}
