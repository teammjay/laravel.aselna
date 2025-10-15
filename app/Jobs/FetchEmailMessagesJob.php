<?php

namespace App\Jobs;

use App\Constants\HdTicketStatus;
use App\Constants\HdTicketTypeCode;
use App\Models\Customer;
use App\Models\CustomerEmail;
use App\Models\HDMessage;
use App\Models\HdMessagePart;
use App\Models\HdTicketType;
use App\Models\Message;
use App\Models\Order;
use App\Models\Sequence;
use App\Models\Store;
use App\Models\StoreMessageAccount;
use App\Utils\EmailMessageUtility;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class   FetchEmailMessagesJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 30000;

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
        try {
            /* get shopify stores*/
            $stores = Store::with('storeType')->get();

            if (count($stores ?? []) > 0) {
                foreach ($stores as $store) {
                    if (env('APP_ENV') === 'local' && $store->name != 'phonil') {
                        continue;
                    }
                    if (env('APP_ENV') === 'local' && $store->storeType->name != 'Ebay') {
                        continue;
                    }
                    Log::info('Processing store: ' . $store->name);
                    $storeMessageAccounts = StoreMessageAccount::with('incomingAccount', 'outgoingAccount')
                        ->where('store_id', $store->id)
                        ->where('active', 1)
                        ->where('api_account', 0)
                        ->get();

                    if (count($storeMessageAccounts ?? []) > 0) {
                        foreach ($storeMessageAccounts as $maccount) {
                            // Dispatch a new job for each store message account
                            ProcessStoreMessageAccountJob::dispatch($maccount, $store, $store->storeType->name);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in FetchEmailMessagesJob: ' . $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
        }
    }

    function getNextTicketNumberForStore($storeId) {
        $key = 'ticket_' . str_replace('-', '', $storeId);

        return DB::transaction(function () use ($key) {
            $sequence = Sequence::lockForUpdate()->find($key);

            if (!$sequence) {
                $sequence = Sequence::create([
                    'key' => $key,
                    'number' => 1
                ]);
            } else {
                $sequence->increment('number');
            }

            return $sequence->number;
        });
    }
}
