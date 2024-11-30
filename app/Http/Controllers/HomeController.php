<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $stores = Store::all();
        return view('welcome', compact('stores'));
    }

    public function downloadFeedFile($id)
    {

    }
}
