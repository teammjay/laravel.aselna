<?php

namespace App\Jobs;

use App\Models\Printer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PrinterSyncJob implements ShouldQueue
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
        DB::connection('easyesale_db')->table('printer')->orderBy('id')
            ->chunk(100, function ($printers) {
                foreach ($printers as $printer) {
                    $this->createSyncPrinters($printer);
                }
            });
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
