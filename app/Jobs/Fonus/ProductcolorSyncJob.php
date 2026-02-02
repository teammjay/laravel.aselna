<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\ProductColor;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductcolorSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 30000;

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
        $this->updateTrackingRecord(['tags' => 'Fonus Product Color']);

        try {
            $colors = DB::connection('fonus_db')->table('tbl_colors')->get();
            foreach ($colors as $color) {
                $this->createOrUpdateColor($color);
            }
        } catch (\Throwable $e) {
            Log::error('Fonus Product Color Sync Job Failed: ' . $e->getTraceAsString());

            CriticalErrorService::report('Product Color Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createOrUpdateColor(mixed $color)
    {
        ProductColor::updateOrCreate([
            'code' => $color->color_id,
        ], [
            'code' => $color->color_id,
            'name' => $color->color,
        ]);
    }
}
