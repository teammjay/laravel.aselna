<?php

namespace App\Jobs;

use App\Models\StoreType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreTypeSyncJob implements ShouldQueue
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
        $storeTypes = DB::connection('fonus_db')->table('tbl_channel')->get();
        foreach ($storeTypes as $storeType) {
            $this->createStoreType($storeType);
        }
    }

    private function createStoreType(mixed $storeType)
    {
        if (StoreType::where(['code' => $storeType->channel])->exists()) {
            return;
        }
        StoreType::create([
            'id' => (string) Str::uuid(),
            'code' => $storeType->channel,
            'name' => $storeType->channel,
        ]);
    }
}
