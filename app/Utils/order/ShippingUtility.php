<?php

namespace App\Utils\order;

use App\Models\ShippingPackageShippingCarrierPackageLink;
use App\Models\ShippingSettingCarrierAccount;
use App\Models\ShippingSolutionCarrierPackage;
use App\Models\ShippingSolutionCarrierService;
use Illuminate\Support\Facades\Log;

class ShippingUtility
{
    public static function assignSolutionService($orderOperation)
    {
        $shippingCarrier = $orderOperation->carrier;
        Log::info('Carrier Id :: ' . $shippingCarrier->id);
        $carrierAccount = ShippingSettingCarrierAccount::where('shipping_carrier_id', $shippingCarrier->id)->first();
        if (!isset($carrierAccount)) {
            return 'Shipping Account is not assigned for ' . $shippingCarrier->name . '. Please assign an active shipping account for this carrier.';
        }
        Log::info('Carrier Account ' . $carrierAccount->id);
        $shippingAccount = $carrierAccount->shippingAccount;
        $shippingSolution = $shippingAccount->shippingSolution;
        $shippingCarrierService = ShippingSolutionCarrierService::where('shipping_service_id', $orderOperation->shipping_carrier_service_id)
            ->where('shipping_solution_id', $shippingSolution->id)->first();
        if (!isset($shippingCarrierService)) {
            return "Unable Shipping Account is not assigned for '.$shippingCarrier->name.'. Please assing an active shipping account for this carrier.";
        }

        $shippingCarrierPackage = $orderOperation->shippingPackage;
        Log::info('Shipping carrier Package Id :: ' . $shippingCarrierPackage->id);
//        Log::info('Shipping carrier service :: ' . $shippingCarrier->id);

//        $shippingCarrierPackages = ShippingPackageShippingCarrierPackageLink::where('shipping_package_id', $shippingPackage->id)
//            ->whereIn('shipping_carrier_package_id', function ($query) use ($shippingCarrier) {
//                $query->select('id')->from('shipping_carrier_package')->where('shipping_carrier_id', $shippingCarrier->id);
//            })->get();
//        if ($shippingCarrierPackages->isEmpty()) {
//            return $shippingPackage->name . ' is not compatible with shipping solution ' . $shippingSolution->name;
//        }

        $shippingSolutionCarrierPackage = ShippingSolutionCarrierPackage::where('shipping_solution_id', $shippingSolution->id)
            ->where('carrier_package_id', $shippingCarrierPackage->id)
            ->first();
        Log::info('Shipping solution carrier package :: ' . $shippingSolutionCarrierPackage->id ?? 'null');
        if (!isset($shippingSolutionCarrierPackage)) {
            return $shippingCarrierPackage->name . ' is not compatible with shipping solution ' . $shippingSolution->name . '. Please assign a compatible shipping package for this shipping solution.';
        }


        //how to save this into orderOperation object?
        $orderOperation->shipping_account = $shippingAccount;
        $orderOperation->shipping_solution_carrier_service = $shippingCarrierService;
        $orderOperation->shipping_solution_carrier_package = $shippingSolutionCarrierPackage;
//        $orderOperation->shipping_carrier_package = $shippingCarrierPackages->first();

        Log::info('Shipping solution service assigned', [
            'order_operation_id' => $orderOperation->id,
            'shipping_account' => $shippingAccount->name,
            'shipping_solution' => $shippingSolution->name,
            'shipping_carrier_service' => $shippingCarrierService->solution_carrier_code,
//            'shipping_carrier_package' => $shippingCarrierPackages->first()->ShippingCarrierPackage->name ?? null
        ]);


        return '';
    }
}
