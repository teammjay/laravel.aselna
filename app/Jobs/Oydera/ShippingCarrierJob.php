<?php

namespace App\Jobs\Oydera;

use App\Models\ShippingCarrier;
use App\Models\ShippingCarrierPackage;
use App\Models\ShippingCarrierService;
use App\Models\ShippingCarrierServiceChannelDetail;
use App\Models\ShippingPackage;
use App\Models\ShippingPackageShippingCarrierPackageLink;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Jobs\Concerns\TrackableJob;

class ShippingCarrierJob implements ShouldQueue
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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Shipping Carriers']);

        try {
            $shippingPackage = DB::connection('easyesale_db')->table('shipping_package')->get();
            foreach ($shippingPackage as $shippingCarrierPackage) {
                $this->createShippingPackage($shippingCarrierPackage);
            }

            $shippingCarriers = DB::connection('easyesale_db')->table('shipping_carrier')->cursor();
            foreach ($shippingCarriers as $shippingCarrier) {
                $this->createShippingCarrier($shippingCarrier);
            }

            $shippingCarrierServices = DB::connection('easyesale_db')->table('shipping_carrier_service')->cursor();
            foreach ($shippingCarrierServices as $shippingCarrierService) {
                $this->createShippingCarrierService($shippingCarrierService);
            }

            $shippingCarrierPackages = DB::connection('easyesale_db')->table('shipping_carrier_package')->cursor();
            foreach ($shippingCarrierPackages as $shippingCarrierPackage) {
                $this->createShippingCarrierPackage($shippingCarrierPackage);
            }

            $shippingCarrierServiceChannelDetails = DB::connection('easyesale_db')->table('shipping_carrier_service_channel_detail')->get();
            foreach ($shippingCarrierServiceChannelDetails as $shippingCarrierServiceChannelDetail) {
                $this->createShippingCarrierServiceChannelDetail($shippingCarrierServiceChannelDetail);
            }

            $shippingPackageLinks = DB::connection('easyesale_db')->table('shipping_package_shipping_carrier_package_link')->get();
            foreach ($shippingPackageLinks as $shippingPackageLink) {
                $this->createShippingPackageShippingCarrierPackageLink($shippingPackageLink);
            }

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

            CriticalErrorService::report('Shipping Carrier Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

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

    private function createShippingCarrierPackage(\stdClass $shippingCarrierPackage)
    {
        $shippingCarrierPackageId = $shippingCarrierPackage->id;
        ShippingCarrierPackage::updateOrCreate([
            'id' => $shippingCarrierPackageId,
        ], [
            'id' => $shippingCarrierPackageId,
            'shipping_carrier_id' => $shippingCarrierPackage->shipping_carrier_id,
            'name' => $shippingCarrierPackage->name,
            'dimensions_length' => $shippingCarrierPackage->dimensions_length,
            'dimensions_width' => $shippingCarrierPackage->dimensions_width,
            'dimensions_height' => $shippingCarrierPackage->dimensions_height,
            'dimensions_length_unit' => $shippingCarrierPackage->dimensions_length_unit,
            'max_weight_value' => $shippingCarrierPackage->max_weight_value,
            'max_weight_weight_unit' => $shippingCarrierPackage->max_weight_weight_unit,
            'non_machinable' => $shippingCarrierPackage->non_machinable
        ]);
    }

    private function createShippingPackage($shippingCarrierPackage)
    {
        Log::info('Shipping Package: ' . json_encode($shippingCarrierPackage));
        ShippingPackage::updateOrCreate([
            'id' => $shippingCarrierPackage->id,
        ], [
            'id' => $shippingCarrierPackage->id,
            'name' => $shippingCarrierPackage->name,
            'client' => $shippingCarrierPackage->client,
            'dimensions_length' => $shippingCarrierPackage->dimensions_length,
            'dimensions_width' => $shippingCarrierPackage->dimensions_width,
            'dimensions_height' => $shippingCarrierPackage->dimensions_height,
            'dimensions_length_unit' => $shippingCarrierPackage->dimensions_length_unit,
            'weight_value' => $shippingCarrierPackage->weight_value,
            'weight_weight_unit' => $shippingCarrierPackage->weight_weight_unit,
        ]);
    }

    private function createShippingPackageShippingCarrierPackageLink(mixed $shippingPackageLink)
    {
        ShippingPackageShippingCarrierPackageLink::updateOrCreate([
            'shipping_package_id' => $shippingPackageLink->shipping_package_id,
            'shipping_carrier_package_id' => $shippingPackageLink->shipping_carrier_package_id,
        ], [
            'shipping_package_id' => $shippingPackageLink->shipping_package_id,
            'shipping_carrier_package_id' => $shippingPackageLink->shipping_carrier_package_id,
        ]);
    }

    private function createShippingCarrierServiceChannelDetail(mixed $shippingCarrierServiceChannelDetail)
    {
        ShippingCarrierServiceChannelDetail::updateOrCreate([
            'id' => $shippingCarrierServiceChannelDetail->id,
            'carrier_service_id' => $shippingCarrierServiceChannelDetail->shipping_carrier_service_id,
            'store_type_id' => $shippingCarrierServiceChannelDetail->channel_id,
        ], [
            'id' => $shippingCarrierServiceChannelDetail->id,
            'carrier_service_id' => $shippingCarrierServiceChannelDetail->shipping_carrier_service_id,
            'store_type_id' => $shippingCarrierServiceChannelDetail->channel_id,
            'code' => $shippingCarrierServiceChannelDetail->code,
        ]);
    }
}
