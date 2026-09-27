<?php

namespace App\Jobs\Concerns;

use App\Models\JobStatus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

trait TrackableJob
{
    public $chainId;

    public $jobId;

    public $parentJobId;

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

        JobStatus::updateOrCreate(
            [
                'job_id' => $jobId
            ],
            [
                'type' => static::class,
                'queue' => $this->queue ?? 'default',
                'status' => 'pending',
                'chain_id' => $this->chainId,
                'parent_job_id' => $this->parentJobId,
                'tags' => $tags,
            ]
        );
    }

    /**
     * Create pending records for all jobs in the chain upfront.
     */
    public function createPendingRecordsForChain(array $chain, string $parentJobId): void
    {
        foreach ($chain as $index => $job) {
            $childJobId = (string) Str::uuid();

            // Set the job ID, chain ID and parent job ID on the child job
            if (method_exists($job, 'setJobId')) {
                $job->setJobId($childJobId);
            }
            if (method_exists($job, 'setChainId')) {
                $job->setChainId($this->chainId);
            }
            if (method_exists($job, 'setParentJobId')) {
                $job->setParentJobId($parentJobId);
            }

            JobStatus::create([
                'job_id' => $childJobId,
                'type' => get_class($job),
                'queue' => $job->queue ?? 'default',
                'status' => 'pending',
                'chain_id' => $this->chainId,
                'parent_job_id' => $parentJobId,
                'order' => $index,
            ]);
        }
    }

    public function updateTrackingRecord(array $updates): void
    {
        // Store-scoped jobs (ScopesToStores) add the task, current store, stores and who started the run
        if (method_exists($this, 'withStoreTrackingTags')) {
            $updates = $this->withStoreTrackingTags($updates);
        }

        Log::info('Updates:'.json_encode($updates));
        JobStatus::where('job_id', $this->jobId ?? null)->update($updates);
    }

    /**
     * Mark parent job as completed when all child jobs are done.
     */
    public function markParentAsCompletedIfAllDone(): void
    {
        if ($this->parentJobId) {
            $pendingChildren = JobStatus::where('parent_job_id', $this->parentJobId)
                ->whereNotIn('status', ['completed', 'failed'])
                ->count();

            if ($pendingChildren === 0) {
                JobStatus::where('job_id', $this->parentJobId)
                    ->update(['status' => 'completed', 'finished_at' => now()]);
            }
        }
    }

    /**
     * Override the job's chain method to pass the chain ID.
     */
    public function withChain(array $chain): array
    {
//        // Use existing chainId or generate new one
//        if (empty($this->chainId)) {
//            $this->chainId = (string) Str::uuid();
//        }
//
//        foreach ($chain as $job) {
//            if (method_exists($job, 'setChainId')) {
//                $job->setChainId($this->chainId);
//            }
//        }

        return parent::withChain($chain);
    }

    public function setJobId($jobId): void
    {
        $this->jobId = $jobId;
    }

    public function setChainId($chainId): void
    {
        $this->chainId = $chainId;
    }

    public function setParentJobId($parentJobId): void
    {
        $this->parentJobId = $parentJobId;
    }

    public function getChainId(): ?string
    {
        return $this->chainId;
    }

    public function getJobId(): ?string
    {
        return $this->jobId;
    }

    public function getParentJobId(): ?string
    {
        return $this->parentJobId;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->updateTrackingRecord([
            'status' => 'failed',
            'output' => $exception->getMessage(),
            'finished_at' => now(),
        ]);

        Log::error(static::class . " Job failed: " . $exception->getMessage());
    }
}
