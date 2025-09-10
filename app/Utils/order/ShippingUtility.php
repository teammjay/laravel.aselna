<?php

namespace App\Utils\order;

use App\Models\ShippingCarrierPackage;
use App\Models\ShippingPackageShippingCarrierPackageLink;
use App\Models\ShippingSettingCarrierAccount;
use App\Models\ShippingSolutionCarrierPackage;
use App\Models\ShippingSolutionCarrierService;
use Illuminate\Support\Facades\Log;

class ShippingUtility
{
    public static function assignSolutionService(&$orderOperation)
    {
        $shippingCarrier = $orderOperation->carrier;
        if(!isset($shippingCarrier)) {
            return 'Shipping Carrier is not assigned for this order operation.';
        }
        Log::info('Carrier Id :: ' . $shippingCarrier->id);
       /* $carrierAccount = ShippingSettingCarrierAccount::with('shippingAccount','shippingAccount.shippingSolution')->where('shipping_carrier_id', $shippingCarrier->id)->where('shipping_setting_id','a0b5fa27-69cc-06f7-8b6b-eb7fca661ea9')->first();*/
        $carrierAccount = ShippingSettingCarrierAccount::with('shippingAccount','shippingAccount.shippingSolution')->where('shipping_carrier_id', $shippingCarrier->id)->where('shipping_account_id','6af8e570-2505-ebab-a60f-3a7f0a5e8da6')->first();
        if (!isset($carrierAccount)) {
            return 'Shipping Account is not assigned for ' . $shippingCarrier->name . '. Please assign an active shipping account for this carrier.';
        }
        Log::info('Carrier Account ' . $carrierAccount->id);
        $shippingAccount = $carrierAccount->shippingAccount;
        $shippingSolution = $shippingAccount->shippingSolution;
        $shippingCarrierService = ShippingSolutionCarrierService::where('shipping_service_id', $orderOperation->shipping_carrier_service_id)
            ->where('shipping_solution_id', $shippingSolution->id)->first();
        Log::info('Shipping solution id :: ' . json_encode($shippingCarrierService));
        if (!isset($shippingCarrierService)) {
            return "Unable Shipping Account is not assigned for '.$shippingCarrier->name.'. Please assing an active shipping account for this carrier.";
        }

        $shippingPackage = $orderOperation->shippingPackage;
//        Log::info('Shipping carrier Package Id :: ' . $shippingCarrierPackage->id);
        Log::info('Shipping carrier service :: ' . $shippingPackage->id);

        $shippingCarrierPackages = ShippingPackageShippingCarrierPackageLink::where('shipping_package_id', $shippingPackage->id)
            ->whereIn('shipping_carrier_package_id', function ($query) use ($shippingCarrier) {
                $query->select('id')->from('shipping_carrier_package')->where('shipping_carrier_id', $shippingCarrier->id);
            })->get();
        if ($shippingCarrierPackages->isEmpty()) {
            return $shippingPackage->name . ' is not compatible with shipping solution ' . $shippingSolution->name;
        }
        $shippingCarrierPackageId= $shippingCarrierPackages[0]->shipping_carrier_package_id;
        $orderOperation->shipping_carrier_package = ShippingCarrierPackage::where('id', $shippingCarrierPackageId)->first();
        Log::info('Shipping carrier package :: ' . json_encode($orderOperation->shipping_carrier_package) ?? 'null');

        Log::info('Shipping carrier package :: ' . $orderOperation->shipping_carrier_package->id ?? 'null');
        Log::info('Shipping solution id :: ' . $shippingSolution->id ?? 'null');
        $shippingSolutionCarrierPackage = ShippingSolutionCarrierPackage::where('shipping_solution_id', $shippingSolution->id)
            ->where('carrier_package_id', $orderOperation->shipping_carrier_package->id)
            ->first();
        Log::info('Shipping solution carrier package :: ' . $shippingSolutionCarrierPackage->id ?? 'null');
        if (!isset($shippingSolutionCarrierPackage)) {
            return $orderOperation->shipping_carrier_package->name . ' is not compatible with shipping solution ' . $shippingSolution->name . '. Please assign a compatible shipping package for this shipping solution.';
        }


        //how to save this into orderOperation object?
        $orderOperation->shipping_account = $shippingAccount;
        $orderOperation->shipping_solution_carrier_service = $shippingCarrierService;
        $orderOperation->shipping_solution_carrier_package = $shippingSolutionCarrierPackage;


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
