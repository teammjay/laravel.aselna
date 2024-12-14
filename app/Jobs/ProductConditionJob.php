<?php

namespace App\Jobs;

use App\Models\ProductCondition;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProductConditionJob implements ShouldQueue
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
        $productCondition = DB::connection('easyesale_db')->table('product_condition')->get();
        foreach ($productCondition as $condition) {
            $this->createProductCondition($condition);
        }
    }

    private function createProductCondition(mixed $condition)
    {
        ProductCondition::updateOrCreate(
            ['id' => $condition->id],
            [
                'id' => $condition->id,
                'name' => $condition->name,
                'code' => $condition->code,
            ]
        );
    }
}
