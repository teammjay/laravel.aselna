<?php

namespace App\Jobs;

use App\Models\ShippingSettingCarrierAccount;
use App\Models\ShippingSolution;
use App\Models\ShippingSolutionCarrierPackage;
use App\Models\ShippingSolutionCarrierService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class ShippingSolutionSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 3000;

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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Shipping Solutions']);

        try {
            DB::connection('easyesale_db')->table('shipping_solution')
                ->orderBy('id', 'desc')
                ->chunk(100, function ($shippingSolutions) {
                    foreach ($shippingSolutions as $shippingSolution) {
                        $this->createSyncShippingSolution($shippingSolution);
                    }
                });

            DB::connection('easyesale_db')->table('shipping_solution_carrier_package')
                ->orderBy('id', 'desc')
                ->chunk(100, function ($shippingSolutionCarrierPackages) {
                    foreach ($shippingSolutionCarrierPackages as $shippingSolutionCarrierPackage) {
                        $this->createSyncShippingSolutionCarrierPackage($shippingSolutionCarrierPackage);
                    }
                });

            DB::connection('easyesale_db')->table('shipping_solution_carrier_service')
                ->orderBy('id', 'desc')
                ->chunk(100, function ($shippingSolutionCarrierServices) {
                    foreach ($shippingSolutionCarrierServices as $shippingSolutionCarrierService) {
                        $this->createSyncShippingSolutionCarrierService($shippingSolutionCarrierService);
                    }
                });

            DB::connection('easyesale_db')->table('shipping_setting_carrier_account')
                ->orderBy('id', 'desc')
                ->chunk(100, function ($shippingSettingCarrierAccounts) {
                    foreach ($shippingSettingCarrierAccounts as $shippingSettingCarrierAccount) {
                        $this->createSyncShippingSettingCarrierAccount($shippingSettingCarrierAccount);
                    }
                });

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

            CriticalErrorService::report('Shipping Solution Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createSyncShippingSolution(mixed $shippingSolution)
    {
        ShippingSolution::updateOrCreate(
            [
                'id' => $shippingSolution->id
            ],
            [
                'id' => $shippingSolution->id,
                'code' => $shippingSolution->code,
                'name' => $shippingSolution->name,
                'production_service_url' => $shippingSolution->production_service_url,
                'sandbox_service_url' => $shippingSolution->sandbox_service_url,
                'shipment_estimate_available' => $shippingSolution->shipment_estimate_available,
            ]
        );
    }

    private function createSyncShippingSolutionCarrierPackage(mixed $shippingSolutionCarrierPackage)
    {
        ShippingSolutionCarrierPackage::updateOrCreate(
            [
                'id' => $shippingSolutionCarrierPackage->id
            ],
            [
                'id' => $shippingSolutionCarrierPackage->id,
                'solution_code' => $shippingSolutionCarrierPackage->solution_code,
                'shipping_solution_id' => $shippingSolutionCarrierPackage->shipping_solution_id,
                'carrier_package_id' => $shippingSolutionCarrierPackage->carrier_package_id,
                'label_size' => $shippingSolutionCarrierPackage->label_size,
            ]
        );
    }

    private function createSyncShippingSolutionCarrierService(mixed $shippingSolutionCarrierService)
    {
        ShippingSolutionCarrierService::updateOrCreate(
            [
                'id' => $shippingSolutionCarrierService->id
            ],
            [
                'id' => $shippingSolutionCarrierService->id,
                'shipping_solution_id' => $shippingSolutionCarrierService->shipping_solution_id,
                'solution_code' => $shippingSolutionCarrierService->solution_code,
                'shipping_service_id' => $shippingSolutionCarrierService->shipping_service_id,
                'solution_carrier_code' => $shippingSolutionCarrierService->solution_carrier_code,
            ]
        );
    }

    private function createSyncShippingSettingCarrierAccount(mixed $shippingSettingCarrierAccount)
    {
        ShippingSettingCarrierAccount::updateOrCreate(
            [
                'id' => $shippingSettingCarrierAccount->id
            ],
            [
                'id' => $shippingSettingCarrierAccount->id,
                'shipping_carrier_id' => $shippingSettingCarrierAccount->shipping_carrier_id,
                'shipping_account_id' => $shippingSettingCarrierAccount->shipping_account_id,
                'shipping_setting_id' => $shippingSettingCarrierAccount->shipping_setting_id,
            ]
        );
    }
}
