<?php

namespace App\Jobs\Oydera;

use App\Models\Printer;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class PrinterSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Printers']);

        try {
            DB::connection('easyesale_db')->table('printer')->orderBy('id')
                ->chunk(100, function ($printers) {
                    foreach ($printers as $printer) {
                        $this->createSyncPrinters($printer);
                    }
                });

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Printer Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createSyncPrinters(mixed $printer)
    {
        $created_by = auth()->user()->name ?? 'admin';

        Printer::updateOrCreate(
            ['id' => $printer->id],
            [
                'id' => $printer->id,
                'client_id' => $printer->client,
                'name' => $printer->name,
                'label_format' => $printer->label_format,
                'status_message' => $printer->status_message,
                'internal_name' => $printer->internal_name,
                'dpi' => $printer->dpi,
                'printer_client_id' => $printer->printer_client_id,
                'status' => $printer->status,
                'printer_type' => $printer->printer_type,
                'created_by' => $created_by,
            ]
        );
    }
}
