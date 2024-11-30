<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UPCController extends Controller
{
    public function upc_code()
    {
        return view('upc.upc_codes');
    }

    public function upc_generate()
    {
        return view('upc.upc_generate');
    }
}
