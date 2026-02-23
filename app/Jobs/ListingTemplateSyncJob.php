<?php

namespace App\Jobs;

use App\Models\ListingTemplate;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class ListingTemplateSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Listing Template']);

        try {
            DB::connection('easyesale_db')->table('listing_template')->orderBy('id')
                ->chunk(1000, function ($listingTemplates) {
                    foreach ($listingTemplates as $listingTemplate) {
                        $this->createListingTemplate($listingTemplate);
                    }
                });

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Listing Template Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createListingTemplate(mixed $listingTemplate)
    {
        ListingTemplate::updateOrCreate([
            'id' => $listingTemplate->id,
        ], [
            'id' => $listingTemplate->id,
            'name' => $listingTemplate->name,
            'code' => $listingTemplate->code,
        ]);
    }
}
