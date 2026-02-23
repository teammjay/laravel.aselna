<?php

namespace App\Jobs;

use App\Constants\HdTicketStatus;
use App\Constants\HdTicketTypeCode;
use App\Helpers\Common;
use App\Jobs\Fonus\ChainCompletionJob;
use App\Models\Customer;
use App\Models\CustomerEmail;
use App\Models\HDMessage;
use App\Models\HdMessagePart;
use App\Models\HdTicketType;
use App\Models\JobStatus;
use App\Models\Message;
use App\Models\Order;
use App\Models\Sequence;
use App\Models\Store;
use App\Models\StoreMessageAccount;
use App\Utils\EmailMessageUtility;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;
use Illuminate\Support\Str;

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
        $this->chainId = (string) Str::uuid();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {

            $this->updateTrackingRecord([
                'status' => 'processing',
                'started_at' => now(),
                'tags' => 'Email Messages'
            ]);

            /* get active store jobs*/
            $store_ids = Common::getActiveStoreJobs('FetchEmailMessagesJob');
            /* get active store jobs*/
            Log::info('Store ids for FetchEmailMessagesJob: ' . json_encode($store_ids));

            /* get shopify stores*/
            $query = Store::with('storeType')->where('store_status','<>',0);
            if(!empty($store_ids)){
                $query->whereIn('id',$store_ids);
            }
            $stores = $query->get();

            if (count($stores ?? []) > 0) {
                foreach ($stores as $store) {
                    /*if (env('APP_ENV') === 'local' && $store->name != 'phonil') {
                        continue;
                    }
                    if (env('APP_ENV') === 'local' && $store->storeType->name != 'Ebay') {
                        continue;
                    }*/

                    /*if (env('APP_ENV') === 'local' && strtolower($store->name) != 'odemobile' && strtolower($store->storeType->name) != 'walmart') {
                        continue;
                    }
                    if( $store->id != 'f5ebde6c-2bcc-03f5-4d62-ef4692f87314'){
                        continue;
                    }*/
                    Log::info('Processing store: ' . $store->name);
                    $storeMessageAccounts = StoreMessageAccount::with('incomingAccount', 'outgoingAccount')
                        ->where('store_id', $store->id)
                        ->where('active', 1)
                        ->where('api_account', 0)
                        ->get();

                    $chain = [];

                    if (count($storeMessageAccounts ?? []) > 0) {
                        foreach ($storeMessageAccounts as $maccount) {
                            // Dispatch a new job for each store message account
                            Log::info('Processing store 2: ' . $store->name);
                            //Bus::chain([])->dispatch(new ProcessStoreMessageAccountJob($maccount, $store, $store->storeType->name));
//                            ProcessStoreMessageAccountJob::dispatch($maccount, $store, $store->storeType->name);
                            $chain[] = new ProcessStoreMessageAccountJob($maccount, $store, $store->storeType->name);
                        }
                    }
                }

                if (count($chain) > 0) {
                    // Add the completion job at the end of the chain to mark parent as completed
                    $chain[] = new ChainCompletionJob($this->jobId);

                    // Create pending record for the completion job
                    $this->createPendingRecordsForChain($chain, $this->jobId);

                    // Store parent job ID for the catch callback
                    $parentJobId = $this->jobId;

                    Bus::chain($chain)
                        ->catch(function (\Throwable $e) use ($parentJobId,$store) {
                            // Mark parent as failed if chain fails
                            JobStatus::where('job_id', $parentJobId)
                                ->update(['status' => 'failed', 'output' => $e->getMessage(), 'finished_at' => now()]);

                            Log::error('Fetch Email Message Chain Failed: ' . $e->getMessage());

                            CriticalErrorService::report('Fetch Email Message Chain Failed', [
                                'marketplace' => $store->name,
                                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                                'method' => __METHOD__
                            ]);
                        })
                        ->dispatch();

                    //Bus::chain($chain)->dispatch();
                }
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Fetch Email Message Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

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
