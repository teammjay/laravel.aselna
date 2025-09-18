<?php

namespace Database\Seeders;

use App\Models\ShippingAccount;
use App\Models\ShippingCarrier;
use App\Models\ShippingCarrierService;
use App\Models\ShippingSettingCarrierAccount;
use App\Models\ShippingSolutionCarrierService;
use Illuminate\Database\Seeder;

class ShippingSolutionGroundAdvantage extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shippingCarrier = ShippingCarrier::where('code','usps')->first();
        $shippingCarrierService = ShippingCarrierService::updateOrCreate(
            [
                'name' => 'Ground Advantage',
                'shipping_carrier_id' => $shippingCarrier->id
            ],
            [
                'name' => 'Ground Advantage',
                'shipping_carrier_id' => $shippingCarrier->id,
                'generic_service_method' => 1,
            ]
        );

        $shippingAccount = ShippingAccount::where('name', 'Endicia Label Server')->first();

        ShippingSettingCarrierAccount::updateOrCreate(
            [
                'shipping_carrier_id' => $shippingCarrier->id,
                'shipping_account_id' => $shippingAccount->id
            ],
            [
                'shipping_carrier_id' => $shippingCarrier->id,
                'shipping_account_id' => $shippingAccount->id
            ]
        );

        ShippingSolutionCarrierService::updateOrCreate(
            [
                'solution_code' => 'GroundAdvantage',
                'shipping_solution_id' => $shippingAccount->shipping_solution_id,
                'shipping_service_id' => $shippingCarrierService->id
            ],
            [
                'solution_code' => 'GroundAdvantage',
                'shipping_solution_id' => $shippingAccount->shipping_solution_id,
                'shipping_service_id' => $shippingCarrierService->id
            ]
        );
    }
}
