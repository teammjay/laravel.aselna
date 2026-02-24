<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\Interfaces;
use App\Models\ProductInterface;
use App\Models\ProductType;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Jobs\Concerns\TrackableJob;

class InterfaceSyncJob implements ShouldQueue
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
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Interface Sync Job Start');
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Interface']);

        try {
            $attributes = DB::connection('easyesale_db')->table('cea_interface')->cursor();
            foreach ($attributes as $attribute) {
                $this->createInterfaces($attribute);
            }
            Log::info('Interface Sync Job End');

            Log::info('Interface Delete Job Start');
            Interfaces::orderBy('id')->chunk(1000, function ($attributes) {
                Log::info('Processing chunk of ' . count($attributes) . ' interfaces for deletion check.');
                $this->checkAndDelete($attributes);
            });
            Log::info('Interface Delete Job End');

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Interface Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

        }

    }

    private function createInterfaces(mixed $attribute)
    {
        Interfaces::updateOrCreate(
            ['id' => $attribute->id],
            [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'code' => $attribute->code,
                'product_type_id' => $attribute->product_type_id,
                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function checkAndDelete($attributes)
    {
        $attributeIds = [];
        foreach ($attributes as $attribute) {
            $attributeIds[] = $attribute->id;
        }

        $existingAttributeIds = DB::connection('easyesale_db')->table('cea_interface')->whereIn('id', $attributeIds)->pluck('id')->toArray();
        $idsToDelete = array_diff($attributeIds, $existingAttributeIds);
        Log::info('Deleting Interface IDs: ' . implode(',', $idsToDelete));

        if (!empty($idsToDelete)) {
            DB::table('interface')->whereIn('id', $idsToDelete)->delete();
        }
    }


}
