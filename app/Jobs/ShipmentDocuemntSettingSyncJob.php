<?php

namespace App\Jobs;

use App\Models\ShipmentDocumentSetting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class ShipmentDocuemntSettingSyncJob implements ShouldQueue
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
        $shippingDocuments = DB::connection('easyesale_db')->table('shipping_document_setting')->get();
        foreach ($shippingDocuments as $shippingdocument) {
            $this->createShippingDocuments($shippingdocument);
        }
    }

    private function createShippingDocuments(mixed $shippingdocument)
    {
//        ShipmentDocumentSetting::updateOrCreate(
//            [
//                'id' => $shippingdocument->id,
//            ],
//            [
//                'id' => $shippingdocument->id,
//                'client' => $shippingdocument->client,
//                'enabled'   => $shippingdocument->enabled,
//                'document_type' => $shippingdocument->document_type,
//                'printer_id' => $shippingdocument->printer_id,
//                'shipping_from_location_id' => $shippingdocument->shipping_from_location_id,
//                'page_size_id' => $shippingdocument->page_size_id,
//                'two_per_page' => $shippingdocument->two_per_page,
//                'pdf_action' => $shippingdocument->pdf_action,
//                'rotate'    => $shippingdocument->rotate,
//            ]
//        );
    }
}
