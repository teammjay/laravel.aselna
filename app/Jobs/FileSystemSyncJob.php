<?php

namespace App\Jobs;

use App\Models\FileSystem;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class FileSystemSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Filesystem']);

        try {
            $listingItemLinksQ = DB::connection('easyesale_db')->table('file_system')
                ->orderBy('id');

            $listingItemLinksQ->chunk(1000, function ($listingItemLinks) {
                foreach ($listingItemLinks as $listingItemLink) {
                    $this->createListingItemLink($listingItemLink);
                }
            });

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('File System Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createListingItemLink(mixed $listingItemLink)
    {
        FileSystem::updateOrCreate([
            'id' => $listingItemLink->id
        ], [
            'id' => $listingItemLink->id,
            'type_' => $listingItemLink->type_,
            'host' => $listingItemLink->host,
            'port' => $listingItemLink->port,
            'login_id' => $listingItemLink->login_id,
            'password' => $listingItemLink->password,
            'root_path' => $listingItemLink->root_path,
            'url_base_path' => $listingItemLink->url_base_path
        ]);
    }
}
