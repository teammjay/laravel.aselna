<?php

namespace App\Jobs;

use App\Constants\OrderOperationStatus;
use App\Models\OrderOperation;
use App\Utils\order\OrderOperationUtility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncFailedMarketplaceShipmentJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = [900, 1200];

    protected $operationId;

    /**
     * Create a new job instance.
     */
    public function __construct($operationId)
    {
        $this->operationId = $operationId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $orderOperation = OrderOperation::with('order', 'order.orderItem', 'order.customer', 'order.customerAddress', 'store', 'store.storeType', 'carrier', 'shipFrom', 'shipFrom.returnAddress', 'shipFrom.fromAddress', 'operationItem', 'operationItem.orderItem')->where('id', $this->operationId)->first();

        if ($orderOperation->status === OrderOperationStatus::MarkAsShipped || $orderOperation->operation_retry_count > 3) {
            return;
        }

        try {

            $response = OrderOperationUtility::createShipment($orderOperation);

            Log::error('Resubmit shipment success (Operation ID):'.$orderOperation->id);

        } catch (\Throwable $e) {

            $orderOperation->increment('operation_retry_count');

            $orderOperation->update([
                'operation_last_error'  => $e->getMessage(),
            ]);

            Log::error('Resubmit shipment failed:'.$e->getMessage());
        }
    }
}
