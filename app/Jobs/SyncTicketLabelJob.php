<?php

namespace App\Jobs;

use App\Models\HdTicketLabel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SyncTicketLabelJob implements ShouldQueue
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
        DB::connection('easyesale_db')->table('hd_ticket_label')->orderBy('id')
            ->chunk(100, function ($ticketLabels) {
                foreach ($ticketLabels as $ticketLabel) {
                    $this->createSyncTicketLabel($ticketLabel);
                }
            });
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
