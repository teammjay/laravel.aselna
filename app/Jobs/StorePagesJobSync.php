<?php

namespace App\Jobs;

use App\Models\StorePage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StorePagesJobSync implements ShouldQueue
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
        DB::connection('easyesale_db')->table('store_page')->orderBy('id')
            ->chunk(1000, function ($storePages) {
                foreach ($storePages as $storePage) {
                    $this->createStorePage($storePage);
                }
            });
    }

    private function createStorePage(mixed $storePage)
    {
        Log::info('Store Page ID: ' . json_encode($storePage));
        StorePage::updateOrCreate(
            [
                'id' => $storePage->id,
            ],[
                'id' => $storePage->id,
                'store_id' => $storePage->store_id,
                'page_type_id' => $storePage->page_type,
                'title' => $storePage->title,
                'text' => $storePage->text,
            ]
        );
    }
}
