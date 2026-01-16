<?php

namespace App\Jobs;

use App\Models\HdTicketLabel;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class SyncTicketLabelJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Ticket Labels']);

        try {
            DB::connection('easyesale_db')->table('hd_ticket_label')->orderBy('id')
                ->chunk(100, function ($ticketLabels) {
                    foreach ($ticketLabels as $ticketLabel) {
                        $this->createSyncTicketLabel($ticketLabel);
                    }
                });
        }catch (\Throwable $e) {

            CriticalErrorService::report('Ticket Labels Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createSyncTicketLabel(mixed $ticketLabel)
    {
        $created_by = auth()->user()->name ?? 'admin';

        HdTicketLabel::updateOrCreate(
            ['id' => $ticketLabel->id],
            [
                'id' => $ticketLabel->id,
                'client_id' => $ticketLabel->client,
                'name' => $ticketLabel->name,
                'parent_id' => $ticketLabel->parent_id,
                'created_by' => $created_by
            ]
        );
    }
}
