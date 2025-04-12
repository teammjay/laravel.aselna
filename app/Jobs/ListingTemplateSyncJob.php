<?php

namespace App\Jobs;

use App\Models\ListingTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ListingTemplateSyncJob implements ShouldQueue
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
        DB::connection('easyesale_db')->table('listing_template')->orderBy('id')
            ->chunk(1000, function ($listingTemplates) {
                foreach ($listingTemplates as $listingTemplate) {
                    $this->createListingTemplate($listingTemplate);
                }
            });
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
