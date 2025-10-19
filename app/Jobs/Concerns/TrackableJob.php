<?php

namespace App\Jobs\Concerns;

use App\Models\JobStatus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

trait TrackableJob
{
    public $chainId;

    public $jobId;

    /**
     * Create a new tracking record for the job.
     */
    public function createTrackingRecord($jobId, $extra = []): void
    {

        // Generate a chain ID if one doesn't exist
        if (empty($this->chainId)) {
            $this->chainId = (string) Str::uuid();
        }

        if (empty($this->jobId)) {
            $this->jobId = $jobId;
        }

        Log::info('Extra Parameter:'.json_encode($extra));
        $tags = '';
        if(!empty($extra)){
            foreach($extra as $key=>$val){
                $tags .= ' '.$val;
            }
        }

        Log::info('Tags:'.$tags);

        JobStatus::create([
            'job_id' => $jobId,
            'type' => static::class,
            'queue' => $this->queue ?? 'default',
            'status' => 'pending',
            'chain_id' => $this->chainId,
            'tags' => $tags,
        ]);
    }

    public function updateTrackingRecord(array $updates): void
    {
        Log::info('Updates:'.json_encode($updates));
        JobStatus::where('job_id', $this->jobId ?? null)->update($updates);
    }

    /**
     * Override the job's chain method to pass the chain ID.
     */
    public function withChain(array $chain): array
    {
        // Use existing chainId or generate new one
        if (empty($this->chainId)) {
            $this->chainId = (string) Str::uuid();
        }

        foreach ($chain as $job) {
            if (method_exists($job, 'setChainId')) {
                $job->setChainId($this->chainId);
            }
        }

        return parent::withChain($chain);
    }

    public function setChainId($chainId): void
    {
        $this->chainId = $chainId;
    }

    public function getChainId(): ?string
    {
        return $this->chainId;
    }
}
