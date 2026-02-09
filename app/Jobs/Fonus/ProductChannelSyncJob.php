<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Catalog;
use App\Models\ProductChannelDetail;
use App\Models\StoreType;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductChannelSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 300000;

    private $existingProductsMap;

    private $storetypes;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord(['tags' => 'Fonus Product Channel']);

        try {
            $this->storetypes = StoreType::pluck('id','name')->toArray();
            $this->existingProductsMap = Catalog::pluck('product_id', 'product_code')->toArray();

            DB::connection('fonus_db')->table('tbl_product_channels')
                ->orderBy('product_id')
                ->chunk(1000, function ($productChannels) {
                    foreach ($productChannels as $productChannel) {
                        $this->createOrUpdateProductChannel($productChannel);
                    }
                });
        }catch (\Throwable $e) {
            Log::error('Fonus Product Channel Sync Job Failed: ' . $e->getTraceAsString());

            CriticalErrorService::report('Product Interface Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createOrUpdateProductChannel(mixed $productChannel)
    {
        $catalog = $this->existingProductsMap[$productChannel->product_id] ?? null;
        $store_type = $this->storetypes[$productChannel->channel] ?? null;
        if (isset($catalog) && isset($store_type)) {
            $existingRecord = ProductChannelDetail::where('product_id', $catalog)
                ->where('store_type_id', $store_type)
                ->first();
            if ($existingRecord) {
                $existingRecord->update([
                    'status' => $productChannel->list
                ]);
            } else {
                ProductChannelDetail::updateOrCreate([
                    'store_type_id' => $store_type,
                    'product_id' => $catalog,
                ],[
                    'store_type_id' => $store_type,
                    'product_id' => $catalog,
                    'status' => $productChannel->list,
                    'created_by' => 'system',
                    'updated_by' => 'system'
                ]);
            }
        }
    }
}
