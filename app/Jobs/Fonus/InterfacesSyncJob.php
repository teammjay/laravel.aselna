<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Interfaces;
use App\Models\ProductType;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterfacesSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 3000;

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
        Log::info('Interface Sync Job Start');
        // Mark as processing when job starts
        $this->updateTrackingRecord([
            'status' => 'processing',
            'started_at' => now(),
            'tags' => 'Fonus Interface'
        ]);

        try {
            $attributes = DB::connection('fonus_db')->table('tbl_attributes')->get();
            foreach ($attributes as $attribute) {
                $this->createInterfaces($attribute);
            }

            Interfaces::orderBy('id')->chunk(1000, function ($attributes) {
                Log::info('Processing chunk of ' . count($attributes) . ' interfaces for deletion check.');
                $this->checkAndDelete($attributes);
            });

            // Mark as completed when job finishes successfully
            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {
            // Mark as failed when job fails
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Fonus Interface Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            throw $e; // Re-throw to trigger chain failure handling
        }
    }

    private function createInterfaces(mixed $attribute)
    {
        $alreadyExists = Interfaces::where('code',$attribute->attribute_id)->first();
        if (isset($alreadyExists)) {return;}
        Log::info('Syncing Interface: ' . $attribute->attribute_id . ' - ' . $attribute->attribute);
        $productType = ProductType::where('code', $attribute->category_id)->first();
        Interfaces::updateOrCreate(
            ['code' => $attribute->attribute_id],
            [
                'name' => $attribute->attribute,
                'notes1' => $attribute->notes1,
                'product_type_id' => $productType ? $productType->id : null,
                'code' => $attribute->attribute_id,
                'created_by' => 'system',
                'updated_by' => 'system',
            ]
        );
    }

    private function checkAndDelete($attributes)
    {
        $attributeIds = [];
        foreach ($attributes as $attribute) {
            $attributeIds[] = $attribute->code;
        }

        $existingAttributeIds = DB::connection('fonus_db')->table('tbl_attributes')
            ->whereIn('attribute_id', $attributeIds)->pluck('attribute_id')->toArray();

        $idsToDelete = array_diff($attributeIds, $existingAttributeIds);

        if (!empty($idsToDelete)) {
            DB::table('interface')->whereIn('code', $idsToDelete)->delete();
        }
    }
}
