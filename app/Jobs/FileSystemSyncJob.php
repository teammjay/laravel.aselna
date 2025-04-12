<?php

namespace App\Jobs;

use App\Models\FileSystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class FileSystemSyncJob implements ShouldQueue
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
        $listingItemLinksQ = DB::connection('easyesale_db')->table('file_system')
            ->orderBy('id');

        $listingItemLinksQ->chunk(1000, function ($listingItemLinks) {
            foreach ($listingItemLinks as $listingItemLink) {
                $this->createListingItemLink($listingItemLink);
            }
        });
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
