<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Models\PostalAddress;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class AddressesSyncJob implements ShouldQueue
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
        DB::connection('easyesale_db')->table('postal_address')->orderBy('create_ts', 'desc')->chunk(100, function ($addresses) {
            foreach ($addresses as $address) {
                $this->createSyncAddress($address);
            }
        });
    }

    private function createSyncAddress(mixed $address)
    {
        PostalAddress::updateOrCreate(
            [
                'id' => $address->id
            ],
            [
                'id' => $address->id,
                'first_name' => $address->first_name,
                'last_name' => $address->last_name,
                'company' => $address->company,
                'address_line_1' => $address->address_line1,
                'address_line_2' => $address->address_line2,
                'address_line_3' => $address->address_line3,
                'city' => $address->city,
                'state_or_region' => $address->state_or_region,
                'postal_code' => $address->postal_code,
                'country' => $address->county,
                'country_code' => $address->country_code,
                'phone' => $address->phone,
                'email' => $address->email,
                'district' => $address->district,
                'client' => $address->client,
            ]
        );
    }
}
