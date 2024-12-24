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
        $listings = ListingFeed::whereNotIn('feed_type',[10,11,50])->get();
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

        $filetPath = $marketplaceService->generateFeed($listingId);
        // give the url to download the file
        return response()->json(['file_path' => $filetPath]);
    }
}
