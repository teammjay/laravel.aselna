<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\Device;
use App\Models\Interfaces;
use App\Models\ProductInterface;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Jobs\Concerns\TrackableJob;

class ProductInterfaces implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Product Interface']);

        try {
            $productAttributes = DB::connection('easyesale_db')->table('product_cea_interface_link')->cursor();
            foreach ($productAttributes as $productAttribute) {
                $this->createProductInterfaces($productAttribute);
            }

            ProductInterface::orderBy('id')->chunk(1000, function ($productInterfaces) {
                $this->checkAndDeleteProductInterfaces($productInterfaces);
            });

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {
            Log::error('Product Interface Sync Job Failed: ' . $e->getTraceAsString());

            CriticalErrorService::report('Product Interface Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createProductInterfaces(mixed $productAttribute)
    {
        if (ProductInterface::where(['product_id' => $productAttribute->product_id, 'interface_id' => $productAttribute->cea_interface_id])->exists()) {
            return;
        }
        if (!Catalog::where('product_id', $productAttribute->product_id)->exists()) {
            return;
        }
        ProductInterface::create([
            'id' => (string)Str::uuid(),
            'product_id' => $productAttribute->product_id,
            'interface_id' => $productAttribute->cea_interface_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function checkAndDeleteProductInterfaces(mixed $productInterfaces)
    {
        $productIds = [];
        $interfaceMap = [];

        foreach ($productInterfaces as $productInterface) {
            $key = $productInterface->product_id . '_' . $productInterface->interface_id;
            $productIds[] = $productInterface->product_id;
            $interfaceMap[$key] = $productInterface->id;
        }

        $existingRecords = DB::connection('easyesale_db')->table('product_cea_interface_link')
            ->whereIn('product_id', array_unique($productIds))
            ->select('product_id', 'cea_interface_id')
            ->get();

        $existingSet = [];
        foreach ($existingRecords as $record) {
            $existingSet[$record->product_id . '_' . $record->cea_interface_id] = true;
        }

        $idsToDelete = [];
        foreach ($interfaceMap as $key => $id) {
            if (!isset($existingSet[$key])) {
                $idsToDelete[] = $id;
            }
        }
        Log::info("ids to delete: " . implode(',', $idsToDelete));

        // Perform batch deletion if needed
        if (!empty($idsToDelete)) {
            ProductInterface::whereIn('id', $idsToDelete)->delete();
        }
    }
}
