<?php

namespace App\Jobs;

use App\Models\ListingGenerationType;
use App\Models\ListingGenerationTypeTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerationTypeAndTemplateSync implements ShouldQueue
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
        $listingGenerationType = DB::connection('easyesale_db')->table('cea_listing_generation_type')->get();
        foreach ($listingGenerationType as $generationType) {
            $this->createGenerationType($generationType);
        }

        $listingGenerationTypeTemplate = DB::connection('easyesale_db')->table('cea_listing_generation_type_template')->get();
        foreach ($listingGenerationTypeTemplate as $generationTypeTemplate) {
            $this->createGenerationTypeTemplate($generationTypeTemplate);
        }
    }

    private function createGenerationType(mixed $generationType)
    {
        ListingGenerationType::updateOrInsert(
            ['code' => $generationType->id],
            [
                'id' => (string) Str::uuid(),
                'allow_duplicates' => $generationType->allow_duplicates,
                'brand_combo' => $generationType->brand_combo,
                'carrier_combo' => $generationType->carrier_combo,
                'code' => $generationType->id,
                'include_carrier' => $generationType->include_carrier,
                'include_device' => $generationType->include_device,
                'name' => $generationType->name,
                'popular_device' => $generationType->popular_device,
                'singleton' => $generationType->singleton,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'system',
                'updated_by' => 'system'
            ]
        );
    }

    private function createGenerationTypeTemplate(mixed $generationTypeTemplate)
    {
        ListingGenerationTypeTemplate::updateOrInsert(
            ['code' => $generationTypeTemplate->id],
            [
                'id' => (string) Str::uuid(),
                'code' => $generationTypeTemplate->id,
                'generation_type_id' => ListingGenerationType::where('code', $generationTypeTemplate->generation_type_id)->value('id'),
                'client' => $generationTypeTemplate->client,
                'template' => $generationTypeTemplate->template,
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => 'system',
                'updated_by' => 'system'
            ]
        );
    }
}
