<?php

namespace App\Http\Controllers;

use App\Jobs\UPCGenerateJob;
use App\Models\Store;
use App\Models\UPCCodes;
use App\Models\UpcGenerate;
use Illuminate\Http\Request;

class UPCController extends Controller
{
    public function upc_code()
    {
        $upcCodes = UPCCodes::all();
        return view('upc.upc_codes',compact('upcCodes'));
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
