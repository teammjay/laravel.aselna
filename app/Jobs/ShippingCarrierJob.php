<?php

namespace App\Jobs;

use App\Models\ShippingCarrier;
use App\Models\ShippingCarrierService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ShippingCarrierJob implements ShouldQueue
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
        $shippingCarriers = DB::connection('easyesale_db')->table('shipping_carrier')->get();
        foreach ($shippingCarriers as $shippingCarrier) {
            $this->createShippingCarrier($shippingCarrier);
        }

        $shippingCarrierServices = DB::connection('easyesale_db')->table('shipping_carrier_service')->get();
        foreach ($shippingCarrierServices as $shippingCarrierService) {
            $this->createShippingCarrierService($shippingCarrierService);
        }
    }

    private function createShippingCarrier(mixed $shippingCarrier)
    {
        ShippingCarrier::updateOrCreate([
            'id' => $shippingCarrier->id,
            'name' => $shippingCarrier->name
        ], [
            'id' => $shippingCarrier->id,
            'name' => $shippingCarrier->name,
            'code' => $shippingCarrier->code,
        ]);
    }

    private function createShippingCarrierService(mixed $shippingCarrierService)
    {
        ShippingCarrierService::updateOrCreate([
            'id' => $shippingCarrierService->id,
        ], [
            'id' => $shippingCarrierService->id,
            'default_package_dimension_height' => $shippingCarrierService->default_package_dimension_height,
            'default_package_dimension_length' => $shippingCarrierService->default_package_dimension_length,
            'default_package_dimension_length_unit' => $shippingCarrierService->default_package_dimension_length_unit,
            'default_package_dimension_width' => $shippingCarrierService->default_package_dimension_width,
            'delivery_time' => $shippingCarrierService->delivery_time,
            'earliest_delivery_days' => $shippingCarrierService->earliest_delivery_days,
            'generic_service_method' => $shippingCarrierService->generic_service_method,
            'included_insurance' => $shippingCarrierService->included_insurance,
            'latest_delivery_days' => $shippingCarrierService->latest_delivery_days,
            'max_weight_value' => $shippingCarrierService->max_weight_value,
            'max_weight_weight_unit' => $shippingCarrierService->max_weight_weight_unit,
            'name' => $shippingCarrierService->name,
            'service_level' => $shippingCarrierService->service_level,
            'shipping_carrier_id' => $shippingCarrierService->shipping_carrier_id,
        ]);
    }
}
