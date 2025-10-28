<?php

namespace App\Http\Controllers;

use App\Helpers\QueryFilters;
use App\Models\ListingRequirement;
use App\Models\StoreType;
use Illuminate\Http\Request;

class ListingRequirementController extends Controller
{
    public function index()
    {
        //$requirements = ListingRequirement::with('storeType')->get();
        $storeTypes = StoreType::orderBy('name')->get();

        return view('listing_requirements.index', compact('storeTypes'));
    }

    public function filter(Request $request)
    {

        //DB::enableQueryLog();
        $draw = $request['draw'];
        $start = $request['start'];
        $perPage = $request['length'];
        $columnIndex = $request['order'][0]['column'];
        $columnName = $request['columns'][$columnIndex]['data'];
        $columnSortOrder = $request['order'][0]['dir'];
        $search = $request['search']['value'];

        // add select
        $query = ListingRequirement::with('storeType')->select('*');

        // Filter by store type
        if ($request->has('store_type_id') && $request->store_type_id != '') {
            $query->where('store_type_id', $request->store_type_id);
        }

        // Filter by type
        if ($search != '') {
            $query->where(function ($q) use ($search){
                $q->whereLike('type','%'.$search.'%')
                    ->orWhere('category', 'LIKE', '%'.$search.'%')
                    ->orWhere('table', 'LIKE', '%'.$search.'%')
                    ->orWhere('column', 'LIKE', '%'.$search.'%')
                    ->orWhere('key_column', 'LIKE', '%'.$search.'%')
                    ->orWhere('key_value', 'LIKE', '%'.$search.'%')
                    ->orWhere('value_column', 'LIKE', '%'.$search.'%');
            });
        }


        // Filter by type
        if ($request->has('type') && $request->type != '') {
            $query->where('type', $request->type);
        }

        // Filter by category
        if ($request->has('category') && $request->category != '') {
            $query->whereLike('category', '%'.$request->category.'%');
        }


        $totalRecords = (clone $query)->toBase()->getCountForPagination();
        $query->orderBy($columnName, $columnSortOrder);
        $requirements = $query->paginate($perPage, ['*'], 'page', floor($start/$perPage)+1);
        $totalRecordwithFilter = $totalRecords;

        $data = [];
        if (!empty((array)$requirements)) {
            foreach ($requirements as $req) {

                $data[] = array(
                    "store_type" => $req->storeType->name ?? '',
                    "category" => $req->category,
                    "type" => $req->type,
                    "table" => $req->table,
                    "column" => $req->column,
                    "key_column" => $req->key_column,
                    "key_value" => $req->key_value,
                    "value_column" => $req->value_column,
                    "action" => '<div class="d-flex" style="gap:5px;"><a href="'.route('listing_requirements.edit', $req->id).'" class="btn btn-sm btn-warning">Edit</a>
                        <form action="'.route('listing_requirements.destroy', $req->id).'" method="POST" style="display:inline-block;">
                            <input type="hidden" name="_token" value="'.csrf_token().'">
                            <input type="hidden" name="_method" value="DELETE">
                            <button class="btn btn-sm btn-danger" onclick="return confirm(\'Delete this requirement?\')">Delete</button>
                        </form></div>',
                );
            }
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecordwithFilter,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data
        );

        return $response;
    }

    public function create()
    {
        return view('listing_requirements.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => 'required|string',
            'type' => 'required|in:column,key_value',
            'table' => 'required|string',
            'column' => 'nullable|string',
            'key_column' => 'nullable|string',
            'key_value' => 'nullable|string',
            'value_column' => 'nullable|string',
        ]);

        ListingRequirement::create($data);
        return redirect()->route('listing_requirements.index')->with('success', 'Requirement added.');
    }

    public function edit(ListingRequirement $listingRequirement)
    {
        return view('listing_requirements.edit', compact('listingRequirement'));
    }

    public function update(Request $request, ListingRequirement $listingRequirement)
    {
        $data = $request->validate([
            'category' => 'required|string',
            'type' => 'required|in:column,key_value',
            'table' => 'required|string',
            'column' => 'nullable|string',
            'key_column' => 'nullable|string',
            'key_value' => 'nullable|string',
            'value_column' => 'nullable|string',
        ]);

        $listingRequirement->update($data);
        return redirect()->route('listing_requirements.index')->with('success', 'Requirement updated.');
    }

    public function destroy(ListingRequirement $listingRequirement)
    {
        $listingRequirement->delete();
        return redirect()->route('listing_requirements.index')->with('success', 'Requirement deleted.');
    }
}
