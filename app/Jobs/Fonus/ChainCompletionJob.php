<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\JobStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ChainCompletionJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 60;

    private string $parentJobIdToComplete;

    /**
     * Create a new job instance.
     */
    public function __construct(string $parentJobId)
    {
        $this->parentJobIdToComplete = $parentJobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Mark this job as processing
        $this->updateTrackingRecord([
            'status' => 'processing',
            'started_at' => now(),
            'tags' => 'Chain Completion'
        ]);

        try {
            // Mark the parent job as completed
            JobStatus::where('job_id', $this->parentJobIdToComplete)
                ->update(['status' => 'completed', 'finished_at' => now()]);

            Log::info('Chain completed successfully. Parent job marked as completed: ' . $this->parentJobIdToComplete);

            // Mark this job as completed
            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {
            Log::error('Chain Completion Job Failed: ' . $e->getMessage());

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            throw $e;
        }
    }
}

