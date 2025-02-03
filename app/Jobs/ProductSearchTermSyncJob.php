<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ProductSearchTerm;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProductSearchTermSyncJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $productSearchTermQ = DB::connection('easyesale_db')->table('product_search_term')
            ->orderBy('id');

        $productSearchTermQ->chunk(1000, function ($productSearchTerms) {
            foreach ($productSearchTerms as $productSearchTerm) {
                $this->createProductSearchTerm($productSearchTerm);
            }
        });
    }

    private function createProductSearchTerm(mixed $productSearchTerm)
    {
        if (Catalog::where('product_id', $productSearchTerm->product_id)->doesntExist()) {
            return;
        }
        ProductSearchTerm::updateOrCreate([
            'id' => $productSearchTerm->id
        ], [
            'id' => $productSearchTerm->id,
            'product_id' => $productSearchTerm->product_id,
            'term' => $productSearchTerm->value,
            'sort_order' => $productSearchTerm->sort_order,
        ]);
    }
}
