<?php

namespace App\Jobs;

use App\Models\HdMessageTemplate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SyncMessageTemplateJob implements ShouldQueue
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
        DB::connection('easyesale_db')->table('hd_message_template')->orderBy('id')
            ->chunk(100, function ($messageTemplates) {
                foreach ($messageTemplates as $messageTemplate) {
                    $this->createSyncMessageTemplate($messageTemplate);
                }
            });
    }

    private function createSyncMessageTemplate(mixed $messageTemplate)
    {
        $created_by = auth()->user()->name ?? 'admin';

        HdMessageTemplate::updateOrCreate(
            ['id' => $messageTemplate->id],
            [
                'id' => $messageTemplate->id,
                'client_id' => $messageTemplate->client,
                'name' => $messageTemplate->name,
                'template_code' => $messageTemplate->template_code,
                'order_operation_type' => $messageTemplate->order_operation_type,
                'store_type_id' => $messageTemplate->channel_id,
                'print_to_file' => $messageTemplate->print_to_file,
                'replacement_order_shipped' => $messageTemplate->replacement_order_shipped,
                'created_by' => $created_by
            ]
        );
    }
}
