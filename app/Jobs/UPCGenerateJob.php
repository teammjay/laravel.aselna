<?php

namespace App\Jobs;

use App\Models\UPCCodes;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class UPCGenerateJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 3000;

    protected $upcData;

    /**
     * Create a new job instance.
     */
    public function __construct($upcData)
    {
        $this->upcData = $upcData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord(['tags' => 'UPC Generate']);

        try {
            $upcData = $this->upcData;
            $store = $upcData['store'];
            $company_prefix = $upcData['company_prefix'];
            $product_number = $upcData['product_number'];
            $number_of_codes = $upcData['number_of_codes'];

            $upcCodes = [];
            $numberCount = 0;
            $i = $product_number;
            while ($numberCount < $number_of_codes) {
                $upcCodesExceptLastOne = $company_prefix . $i;
                while (strlen($upcCodesExceptLastOne) < 11) {
                    $upcCodesExceptLastOne = $upcCodesExceptLastOne . rand(0, 9); // Add a random digit at the beginning
                }
                // create check sum digit by this formula
                // The digits in odd positions are: 8, 0, 1, 1, 0, 9, their sum is 19, multiplied by 3 it is 57;
                //The digits in even positions (except the last one) are: 1, 0, 2, 1, 0, their sum is 4, added to the number above it is 61;
                //Dividing 61 by 10 gives us 1 as a remainder;
                //It is not zero, so subtracting it from 10 to get the check digit: 9.
                $checkSum = 0;
                for ($j = 0; $j < strlen($upcCodesExceptLastOne); $j++) {
                    $checkSum += $j % 2 === 0 ? $upcCodesExceptLastOne[$j] * 3 : $upcCodesExceptLastOne[$j];
                }
                $checkSum = $checkSum % 10;
                $checkSum = $checkSum === 0 ? 0 : 10 - $checkSum;
                if (UPCCodes::where('upc_code', $upcCodesExceptLastOne . $checkSum)->exists()) {
                    $i++;
                    continue;
                }
                $upcCodes[] = [
                    'upc_code' => $upcCodesExceptLastOne . $checkSum,
                    'product_number' => $i
                ];
                $i++;
                $numberCount++;
            }

            $lastSequenceId = UPCCodes::where('store', $store)->orderBy('sequence_id', 'desc')->first();
            $nextSequenceId = $lastSequenceId ? $lastSequenceId->sequence_id + 1 : 1;

            $bulkData = [];
            foreach ($upcCodes as $upcCode) {
                $bulkData[] = [
                    'id' => (string)\Illuminate\Support\Str::uuid(),
                    'upc_code' => $upcCode['upc_code'],
                    'company_prefix' => $company_prefix,
                    'product_number' => $upcCode['product_number'],
                    'sequence_id' => $nextSequenceId++,
                    'store' => $store,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::transaction(function () use ($bulkData) {
                $chunks = array_chunk($bulkData, 1000);
                foreach ($chunks as $chunk) {
                    UPCCodes::insert($chunk);
                }
            });
        }catch (\Throwable $e) {

            CriticalErrorService::report('UPC Generate Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());

        }
    }
}
