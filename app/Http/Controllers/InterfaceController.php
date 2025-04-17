<?php

namespace App\Http\Controllers;

use App\Helpers\QueryFilters;
use App\Http\Requests\InterfaceRequest;
use App\Models\Interfaces;
use App\Models\ProductType;
use App\Services\DataTable\DataTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterfaceController extends Controller
{
    public function index()
    {
        // get the data in the pagination format for datatable to display
        return view('interface.list');
    }

    public function getInterfaceData(Request $request)
    {
        $totalRecords = Interfaces::query()->count();
        $query = Interfaces::query()
            ->select('interface.*')
            ->with('ProductType');

        $dataTableService = new DataTableService($query, $request);

        $dataTableService->setCustomSorting(function ($query, $request) {
            $columnIndex = $request->input('order.0.column', 0);
            $sortColumn = $request->input('columns.' . $columnIndex . '.data', 'id');
            $columnName = $request->input('columns.' . $columnIndex . '.name', 'id');
            $sortDirection = $request->input('order.0.dir', 'asc');
            if ($columnName == 'product_type') {
                $query->join('product_type', 'product_type.id', '=', 'interface.product_type_id')
                    ->orderBy('product_type.name', $sortDirection);
            } else {
                // Default sorting logic
                $query->orderBy($sortColumn, $sortDirection);
            }
        });

        $dataTableService->setCustomSearch(function ($query, $request) {
            $query
                ->when($request->input('name'), function ($q) use ($request) {
                    QueryFilters::applyFilter($q, $request, 'name', 'name_operator', 'name');
                })
                ->when($request->input('code'), function ($q) use ($request) {
                    QueryFilters::applyFilter($q, $request, 'code', 'code_operator', 'code');
                })
                ->when($request->input('product_type'), function ($query) use ($request) {
                    $query->whereHas('ProductType', function ($q) use ($request) {
                        QueryFilters::applyFilter($q, $request, 'name', 'product_type_operator', 'product_type');
                    });
                });
        });

        $data = $dataTableService->handle();

//        $queryLog = DB::getQueryLog();
//        foreach ($queryLog as $query) {
//            Log::info('Query: ' . $query['query']);
//            Log::info('Bindings: ' . implode(', ', $query['bindings']));
//        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $data->total(),
            'data' => $data->items(),
        ]);
    }

    public function create()
    {
        $productTypes = ProductType::all();
        return view('interface.add', compact('productTypes'));
    }

    public function store(InterfaceRequest $request)
    {
        $request->validated();

        $created_by = auth()->user()->name ?? 'admin';
        $request->merge(['created_by' => $created_by]);

        try {
            // save the data to the database
            Interfaces::create($request->all());
            // redirect to the interfaces list with success message
            return redirect()->route('interface.list')->with('success', 'interface created successfully.');
        } catch (\Exception $e) {
            // handle any errors that may occur
            return redirect()->back()->withErrors(['error' => 'An error occurred while creating the interface. Please try again.']);
        }
    }

    public function edit($id)
    {
        $interface = Interfaces::findOrFail($id);
        $productTypes = ProductType::all();
        return view('interface.add', compact('interface', 'productTypes'));
    }

    public function update(InterfaceRequest $request, $id)
    {
        $request->validated();

        $updated_by = auth()->user()->name ?? 'admin';
        $request->merge(['updated_by' => $updated_by]);

        // save the data to the database
        Interfaces::findOrFail($id)->update($request->all());

        // redirect to the interfaces list
        return redirect()->route('interface.list');
    }

    public function destroy($id)
    {
        // delete the interface
//        Interfaces::findOrFail($id)->delete();

        // redirect to the interfaces list
        return redirect()->route('interface.list');
    }
}
