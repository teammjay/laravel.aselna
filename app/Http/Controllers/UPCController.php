<?php

namespace App\Http\Controllers;

use App\Jobs\UPCGenerateJob;
use App\Models\Store;
use App\Models\UPCCodes;
use App\Models\UpcGenerate;
use App\Services\DataTable\DataTableService;
use Illuminate\Http\Request;

class UPCController extends Controller
{
    public function upc_code()
    {
        $upcCodes = UPCCodes::all();
        $stores = Store::with('storeType')->orderBy('name', 'asc')->get();
        return view('upc.upc_codes',compact('upcCodes','stores'));
    }

    public function getUpcData(Request $request)
    {
        $totalRecords = UPCCodes::query()
            // ->when($request->input('store_id'), function ($query, $storeId) {
            //     $query->where('store', $storeId);
            // })
            ->count();
        $query = UPCCodes::query()
            ->when($request->input('store_id'), function ($query, $storeId) {
                $query->where('store', $storeId);
            })
            ->with('storeData', 'storeData.storeType');

        $dataTableService = new DataTableService($query, $request);

        $dataTableService->setCustomSorting(function ($query, $request) {
            $columnIndex = $request->input('order.0.column', 0);
            $sortColumn = $request->input('columns.' . $columnIndex . '.data', 'id');
            $columnName = $request->input('columns.' . $columnIndex . '.name', 'id');
            $sortDirection = $request->input('order.0.dir', 'asc');
            if ($columnName === 'store') {
                $query->join('store', 'store.id', '=', 'upc_codes.store')
                    ->join('store_type', 'store_type.id', '=', 'store.store_type_id')
                    ->orderByRaw("CONCAT(store.name, ' - ', store_type.name) $sortDirection");
            } else {
                // Default sorting logic
                $query->orderBy($sortColumn, $sortDirection);
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

    public function upc_generate()
    {
        $upcGenerate = UpcGenerate::all();
        return view('upc.upc_generate',compact('upcGenerate'));
    }

    public function create_upc_generate(Request $request)
    {
        $validatedData = $request->validate([
            'store' => 'required',
            'company_prefix' => 'required',
            'product_number' => 'required',
            'number_of_codes' => 'required',
        ]);

        UpcGenerate::create($validatedData);

        UPCGenerateJob::dispatch($validatedData);

        return response()->json(['message' => 'UPC Codes generated successfully']);
    }

    public function getStoresData()
    {
        $stores = Store::with('storeType')->orderBy('name')->get();
        return response()->json($stores);
    }
}
