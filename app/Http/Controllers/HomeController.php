<?php

namespace App\Http\Controllers;

use App\Models\ListingFeed;
use App\Models\Store;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $stores = Store::with('storeType')->get();
        $listings = ListingFeed::all();
        return view('welcome', compact('stores','listings'));
    }

    public function downloadFeedFile($id)
    {

    }
}
