<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Catalog;
use App\Models\Interfaces;
use App\Models\ProductInterface;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductInterfacesSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 300000;

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
        // Mark as processing when job starts
        $this->updateTrackingRecord([
            'status' => 'processing',
            'started_at' => now(),
            'tags' => 'Fonus Product Interface'
        ]);

        try {
            $productAttributes = DB::connection('fonus_db')->table('tbl_product')->get();
            foreach ($productAttributes as $productAttribute) {
                $this->createProductInterfaces($productAttribute);
            }

            ProductInterface::orderBy('id')->chunk(1000, function ($productInterfaces) {
                $this->checkAndDeleteProductInterfaces($productInterfaces);
            });

            // Mark as completed when job finishes successfully
            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {
            Log::error('Fonus Product Interface Sync Job Failed: ' . $e->getTraceAsString());

            // Mark as failed when job fails
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Product Interface Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            throw $e; // Re-throw to trigger chain failure handling
        }
    }

    private function createProductInterfaces(mixed $productAttribute)
    {
        if ($productAttribute->attribute_id == null) {
            return;
        }
        $product = Catalog::select('product_id')->where('product_code', $productAttribute->product_id)->first();
        $attribute = Interfaces::where('code', $productAttribute->attribute_id)->first();
        if (!isset($product) || !isset($attribute)) {
            return;
        }
        $alreadyExists = ProductInterface::where('product_id', $product->id)
            ->where('interface_id', $attribute->id)
            ->first();
        if (isset($alreadyExists)) {
            return;
        }
        ProductInterface::updateOrCreate([
            'product_id' => $product->product_id,
            'interface_id' => $attribute->id,
        ],[
            'product_id' => $product->product_id,
            'interface_id' => $attribute->id,
        ]);
    }

    private function checkAndDeleteProductInterfaces($productInterfaces)
    {
        foreach ($productInterfaces as $productInterface) {
            $productId = $productInterface->product->product_code;
            $interfaceId = $productInterface->interfaces->code;
            $existsInFonus = DB::connection('fonus_db')->table('tbl_product')
                ->where('product_id', $productId)
                ->where('attribute_id', $interfaceId)
                ->first();
            if (!isset($existsInFonus)) {
                Log::info("Deleting Product Interface :: Product ID: " . $productId . " Interface ID: " . $interfaceId);
                $productInterface->delete();
            }
        }
    }
}
