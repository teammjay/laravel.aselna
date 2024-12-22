<?php

namespace App\Http\Controllers;

use App\Models\ListingFeed;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceServiceFactory;
use App\Utils\XmlUtility;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $stores = Store::with('storeType')->get();
        $listings = ListingFeed::limit(2000)->get();
        return view('welcome', compact('stores','listings'));
    }

    public function generateFeedFile(Request $request)
    {
        $request->validate([
            'listing_id' => 'required',
        ]);

        $listingId = $request->listing_id;
        $listingFeed = ListingFeed::where('id',$listingId)->first();

        // get StoreType froms store that is inside listingfeed
        $storeType = $listingFeed->store->storeType->name;

        $marketplaceService = MarketplaceServiceFactory::create($storeType);

        $marketplaceService->generateFeed($listingId);

        return redirect()->route('home')->with('success', 'Feed file generated successfully');
    }
}
