<?php

namespace App\Jobs;

use App\Models\Printer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SystemPrinterSyncJob implements ShouldQueue
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
        DB::connection('aselna_printer_db')->table('Printers')
            ->orderBy('id')
            ->chunk(100, function ($printers) {
                foreach ($printers as $printer) {
                    $this->syncPrinter($printer);
                }
            });
    }

    /**
     * Sync a printer from the external database to the local database.
     *
     * @param mixed $printer
     * @return void
     */
    private function syncPrinter(mixed $printer): void
    {
        $created_by = Auth::user()->name ?? 'admin';

        Printer::updateOrCreate(
            ['id' => $printer->id],
            [
                'id' => $printer->id,
                'name' => $printer->name,
                'status_message' => $printer->status,
                'status' => $printer->status === 'active' ? 1 : 0,
                'printer_type' => 0,
                'label_format' => 2,
                'internal_name' => $printer->systemId,
                'ip' => $printer->ip,
                'port' => $printer->port,
                'created_by' => $created_by,
                'updated_by' => $created_by
            ]
        );
    }
}
