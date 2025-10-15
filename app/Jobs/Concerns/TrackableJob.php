<?php

namespace App\Jobs\Concerns;

use App\Models\JobStatus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

trait TrackableJob
{
    public $chainId;

    /**
     * Create a new tracking record for the job.
     */
    public function createTrackingRecord($jobId): void
    {

        // Generate a chain ID if one doesn't exist
        if (empty($this->chainId)) {
            $this->chainId = (string) Str::uuid();
        }

        JobStatus::create([
            'job_id' => $jobId,
            'type' => static::class,
            'queue' => $this->queue ?? 'default',
            'status' => 'pending',
            'chain_id' => $this->chainId,
        ]);
    }

    /**
     * Override the job's chain method to pass the chain ID.
     */
    public function withChain(array $chain): array
    {
        $this->chainId = (string) Str::uuid();

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
}
