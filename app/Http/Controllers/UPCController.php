<?php

namespace App\Http\Controllers;

use App\Helpers\QueryFilters;
use App\Jobs\UPCGenerateJob;
use App\Models\Store;
use App\Models\UPCCodes;
use App\Models\UpcGenerate;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UPCController extends Controller
{
    public function upc_code()
    {
        $stores = Store::with('storeType')->orderBy('name', 'asc')->get();
        return view('upc.upc_codes',compact('stores'));
    }

    public function getUpcData(Request $request)
    {
        $query = UPCCodes::query();

        if ($request->filled('store_id')) {
            $query->where('store', $request->input('store_id'));
        }
        QueryFilters::applyFilter($query, $request, 'company_prefix', 'prefix_operator', 'prefix');
        QueryFilters::applyFilter($query, $request, 'product_number', 'product_number_operator', 'product_number');
        QueryFilters::applyFilter($query, $request, 'upc_code', 'upc_code_operator', 'upc_code');
        $this->applyNumberFilter($query, $request, 'sequence_id', 'sequence_id_operator', 'sequence_id', false);
        if ($request->filled('is_used')) {
            $query->where('is_used', $request->input('is_used'));
        }

        // counting ~700k rows is the slow part, so only count twice when a filter narrows it
        $recordsTotal = UPCCodes::count();
        $recordsFiltered = empty($query->getQuery()->wheres) ? $recordsTotal : (clone $query)->count();

        // each sort is backed by an index (see add_listing_indexes_to_upc_codes_table); store is not
        // sortable here because ordering every code by store name sorts the whole table
        $direction = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';
        $sortColumn = $request->input('columns.' . $request->input('order.0.column', 0) . '.data');
        $sorts = [
            'company_prefix' => ['company_prefix', 'product_number'],
            'product_number' => ['product_number'],
            'upc_code' => ['upc_code'],
            'is_used' => ['is_used'],
            'sequence_id' => ['sequence_id'],
        ];
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 10);
        if ($length <= 0) {
            $length = $recordsFiltered;
        }

        // the back half of the list is read backwards, so the last page skips a few rows instead of ~700k
        $reverse = $start > $recordsFiltered / 2;
        if ($reverse) {
            $direction = $direction === 'asc' ? 'desc' : 'asc';
            $take = max(0, min($length, $recordsFiltered - $start));
            $skip = max(0, $recordsFiltered - $start - $take);
        } else {
            $take = $length;
            $skip = $start;
        }

        foreach ($sorts[$sortColumn] ?? $sorts['company_prefix'] as $column) {
            $query->orderBy($column, $direction);
        }

        $data = $query->with('storeData', 'storeData.storeType')->skip($skip)->take($take)->get();
        if ($reverse) {
            $data = $data->reverse()->values();
        }

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function upc_generate()
    {
        $stores = Store::with('storeType')->orderBy('name', 'asc')->get();
        return view('upc.upc_generate', compact('stores'));
    }

    public function getUpcGenerateData(Request $request)
    {
        $query = UpcGenerate::query()->select('upc_generates.*');

        if ($request->filled('store_id')) {
            $query->where('upc_generates.store', $request->input('store_id'));
        }
        QueryFilters::applyFilter($query, $request, 'upc_generates.company_prefix', 'prefix_operator', 'prefix');
        $this->applyNumberFilter($query, $request, 'upc_generates.product_number', 'product_number_operator', 'product_number');
        $this->applyNumberFilter($query, $request, 'upc_generates.number_of_codes', 'number_of_codes_operator', 'number_of_codes');

        $recordsTotal = UpcGenerate::count();
        $recordsFiltered = (clone $query)->count();

        $direction = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';
        $sortColumn = $request->input('columns.' . $request->input('order.0.column', 0) . '.data');
        switch ($sortColumn) {
            case 'store':
                $query->join('store', 'store.id', '=', 'upc_generates.store')
                    ->leftJoin('store_type', 'store_type.id', '=', 'store.store_type_id')
                    ->orderByRaw("CONCAT(store.name, ' - ', COALESCE(store_type.name, '')) $direction");
                break;
            case 'product_number':
                // stored as a string, but sorted as the number it is
                $query->orderByRaw("CAST(upc_generates.product_number AS UNSIGNED) $direction");
                break;
            case 'number_of_codes':
                $query->orderBy('upc_generates.number_of_codes', $direction);
                break;
            default:
                $query->orderBy('upc_generates.company_prefix', $direction);
        }
        // tie-breakers so a page never repeats or skips rows that share the sorted value
        $query->orderByRaw('CAST(upc_generates.product_number AS UNSIGNED)')->orderBy('upc_generates.id');

        $length = (int) $request->input('length', 10);
        $data = $query->with('storeData', 'storeData.storeType')
            ->when($length > 0, fn ($query) => $query->skip((int) $request->input('start', 0))->take($length))
            ->get();

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function create_upc_generate(Request $request)
    {
        $duplicateMessage = 'A UPC generate with this company prefix, start product number and number of codes already exists.';
        $validatedData = $request->validate([
            'store' => 'required|exists:store,id',
            'company_prefix' => ['required', function ($attribute, $value, $fail) use ($request) {
                // a company prefix belongs to one store only
                $otherStoreId = UpcGenerate::where('company_prefix', $value)->where('store', '!=', $request->input('store'))->value('store')
                    ?? UPCCodes::where('company_prefix', $value)->where('store', '!=', $request->input('store'))->value('store');
                if ($otherStoreId) {
                    $otherStore = Store::with('storeType')->find($otherStoreId);
                    $storeName = $otherStore ? $otherStore->name . ($otherStore->storeType ? ' - ' . $otherStore->storeType->name : '') : 'another store';
                    $fail('Company prefix ' . $value . ' is already used by ' . $storeName . '.');
                }
            }],
            'product_number' => 'required|integer|min:0',
            'number_of_codes' => ['required', 'integer', 'min:1',
                Rule::unique('upc_generates', 'number_of_codes')
                    ->where('company_prefix', $request->input('company_prefix'))
                    ->where('product_number', $request->input('product_number')),
            ],
        ], [
            'number_of_codes.unique' => $duplicateMessage,
        ]);

        try {
            UpcGenerate::create($validatedData);
        } catch (UniqueConstraintViolationException $e) {
            // same request submitted twice at once; the unique index stops the second one
            return response()->json(['message' => $duplicateMessage, 'errors' => ['number_of_codes' => [$duplicateMessage]]], 422);
        }

        UPCGenerateJob::dispatch($validatedData);

        return response()->json(['message' => 'UPC Codes generated successfully']);
    }

    /**
     * =, > and < compared as numbers. QueryFilters::applyFilter treats > as a date and has no <.
     * $cast for a numeric string column (product_number); an int column is compared as is so its index is used.
     */
    private function applyNumberFilter($query, Request $request, string $field, string $operatorKey, string $valueKey, bool $cast = true): void
    {
        $value = $request->input($valueKey);
        if ($value === null || $value === '' || !is_numeric($value)) {
            return;
        }

        $operator = in_array($request->input($operatorKey), ['>', '<'], true) ? $request->input($operatorKey) : '=';
        if ($cast) {
            $query->whereRaw("CAST($field AS UNSIGNED) $operator ?", [(int) $value]);
        } else {
            $query->where($field, $operator, (int) $value);
        }
    }

    public function getStoresData()
    {
        $stores = Store::with('storeType')->orderBy('name')->get();
        return response()->json($stores);
    }
}

