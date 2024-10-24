<?php

namespace App\Http\Controllers;

use App\Models\Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class CatalogController extends Controller
{

    private $perPage = 10;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // get the data in the pagination format for datatable to display
        $products = Catalog::select('product_id','product_title', 'product_type', 'product_manufacturer', 'product_price', 'product_sku','product_description','product_status','product_condition')
            ->orderBy('product_id','desc')
            ->paginate($this->perPage);

        return view('catalog.list')->with('products', $products)->with('perPage', $this->perPage);
    }

    /**
     * Filter listing.
     */
    public function filter(Request $request)
    {
        $query = Catalog::query();

        $perPage = $request->input('perPage', $this->perPage);

        // Filter by product title
        if ($request->has('name') && $request->name != '') {
            $query->WhereLike('product_title', '%'.$request->name.'%');
        }

        // Filter by price range
        if ($request->has('price_min') && $request->price_min != '') {
            $query->where('product_price', '>=', $request->price_min);
        }

        if ($request->has('price_max') && $request->price_max != '') {
            $query->where('product_price', '<=', $request->price_max);
        }

        // Filter by product status
        if ($request->has('status') && $request->status != '') {
            $query->where('product_status', $request->status);
        }

        $products = $query->paginate($perPage); // Paginate results

        //echo $query->toSql();die;

        // Return a view or JSON
        if ($request->ajax()) {
            return response()->json([
                'html' => view('catalog.product_list', compact('products'))->render(),
                'pagination' => view('catalog.pagination', compact('products'))->render(),
            ]);
        }

        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('catalog.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_title' => 'required|string|max:30',
            'product_type' => 'required|string',
            'product_manufacturer' => 'required|integer',
            'product_price' => 'required|decimal:2',
            'product_sku' => 'required|string|max:225',
            'product_description' => 'nullable|string'
        ]);

        // save the data to the database
        try {
            /*Catalog::create([
                'product_title' => $request->product_title,
                'product_type' => $request->product_type,
                'product_manufacturer' => $request->product_manufacturer,
                'product_price' => $request->product_price,
                'product_sku' => $request->product_sku,
                'product_description' => $request->product_description,
                'product_status' => 'active',
                'product_stock_quantity' => 10,
                'product_condition' => 'New'
            ]);*/

            Catalog::create($request->all());

            return redirect()->route('catalog.list')->with('success', 'Product created successfully!');

        }catch (QueryException $e) {
            // Handle the exception and show an error message
            Log::error('Error saving user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create product. Please try again.');
        }

        // redirect to the brands list

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $product = Catalog::findOrFail($id); // Retrieve the product by ID
        return view('catalog.update', compact('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'product_title' => 'required|string|max:30',
            'product_type' => 'required|string',
            'product_manufacturer' => 'required|integer',
            'product_price' => 'required|decimal:2',
            'product_sku' => 'required|string|max:225',
            'product_description' => 'nullable|string'
        ]);

        // save the data to the database
        try {

            $product = Catalog::findOrFail($id); // Retrieve the product by ID

            // Update the product details
            /*$product->update([
                'product_title' => $request->product_title,
                'product_type' => $request->product_type,
                'product_manufacturer' => $request->product_manufacturer,
                'product_price' => $request->product_price,
                'product_sku' => $request->product_sku,
                'product_description' => $request->product_description,
                'product_status' => 'active',
                'product_stock_quantity' => 10,
                'product_condition' => 'New'
            ]);*/


            $product->update($request->all());

            //echo $product->toSql();die;

            return redirect()->route('catalog.list')->with('success', 'Product updated successfully!');

        }catch (QueryException $e) {
            // Handle the exception and show an error message
            Log::error('Error saving user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update product. Please try again.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Find the user by id
        $product = Catalog::find($id);

        if ($product) {
            $product->delete(); // Delete the user

            // Return a success response
            return response()->json(['success' => 'Product deleted successfully!']);
        }

        // If the user doesn't exist, return an error
        return response()->json(['error' => 'Product not found!'], 404);
    }
}
