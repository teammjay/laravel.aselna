<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Constants\OrderOperationStatus;
use App\Helpers\Common;
use App\Models\Catalog;
use App\Models\OrderOperation;
use App\Utils\order\OrderOperationUtility;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResubmitLabelPrintedShipmentJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'PrintLabel']);

        try {
            $operations = OrderOperation::with('order', 'operationItem', 'store', 'store.storeType')
                ->where('status', OrderOperationStatus::LabelPrinted)
                ->whereNotNull('tracking_number')
                ->get();

            if ($operations) {
                foreach ($operations as $operation) {
                    Log::info('Resubmit Operation:' . $operation->id);
                    $operation->resubmit_count = $operation->resubmit_count ?? 0 + 1;
                    $operation->save();

                    $request = [];
                    /*$store = $operation->store;

                    $package_id = '';
                    $product_total_weight = Common::calculateProductWeight($operation);

                    $product_weight_unit = 0;

                    if(isset($operation->shipping_package_id)) {
                        $request['carrier_package'] = $operation->shipping_package_id;
                    }

                    if (count($operation->operationItem ?? []) > 0) {
                        $product_weight = 0;
                        foreach ($operation->operationItem as $oitem) {
                            if (isset($oitem->orderItem->product)) {
                                $product_id = $oitem->orderItem->product->product_id;
                                if ($product_id) {
                                    $product_detail = Catalog::findOrFail($product_id);
                                    if (!isset($operation->shipping_package_id)) {
                                        if (($product_detail->product_weight * $oitem->quantity) > $product_weight) {
                                            $package_id = $product_detail->shipping_package_id;
                                        }
                                        if ($package_id) {
                                            $request['carrier_package'] = $package_id;
                                        }
                                    } else {
                                        $request['carrier_package'] = $operation->shipping_package_id;
                                    }

                                    $product_weight = $product_detail->product_weight * $oitem->quantity;
                                    $product_weight_unit = $product_detail->product_weight_unit;
                                }
                            }
                        }
                    }

                    $request['weight'] = $operation->weight_value ?? $product_total_weight;
                    $request['weight_type'] = isset($operation->weight_weight_unit) ? $operation->weight_weight_unit : $product_weight_unit;*/
                    $request['label_generation'] = 1;
                    $request['resubmit'] = 1;

                    $successRes = OrderOperationUtility::processShipment($operation, new Request($request));

                    Log::info('Resubmit Operation Status:' . $successRes);
                }
            }

            Log::info('Resubmit Operation: No operation found');

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);

        }catch (\Throwable $e) {

            CriticalErrorService::report('Resubmit Label Printed Shipment Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }
}
