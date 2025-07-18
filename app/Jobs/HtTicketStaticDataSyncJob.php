<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\HdTicketType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class HtTicketStaticDataSyncJob implements ShouldQueue
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
        $ticketTypes = DB::connection('easyesale_db')->table('hd_ticket_type')->get();
        foreach ($ticketTypes as $ticketType) {
            $this->createTicketType($ticketType);
        }
    }

    private function createTicketType(mixed $ticketType)
    {
        HdTicketType::updateOrCreate(
            ['id' => $ticketType->id],
            [
                'id' => $ticketType->id,
                'code' => $ticketType->code,
                'name' => $ticketType->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
