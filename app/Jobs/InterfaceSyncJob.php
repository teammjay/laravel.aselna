<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\Interfaces;
use App\Models\ProductInterface;
use App\Models\ProductType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InterfaceSyncJob implements ShouldQueue
{
    use Queueable;

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
        $attributes = DB::connection('easyesale_db')->table('cea_interface')->cursor();
        foreach ($attributes as $attribute) {
            $this->createInterfaces($attribute);
        }

    }

    private function createInterfaces(mixed $attribute)
    {
        Interfaces::updateOrCreate(
            ['id' => $attribute->id],
            [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'code' => $attribute->code,
                'product_type_id' => $attribute->product_type_id,
                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }




}
