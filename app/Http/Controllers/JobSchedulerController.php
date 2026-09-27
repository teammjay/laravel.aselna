<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunJobRequest;
use App\Models\Job;
use App\Models\ScheduledJob;
use App\Models\ScheduleParentJob;
use App\Services\Jobs\StoreScopedJobRunner;
use App\Utils\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class JobSchedulerController extends Controller
{
    public function __construct(private StoreScopedJobRunner $runner)
    {
    }

    public function index()
    {
        $jobs = ScheduledJob::all();

        // Jobs whose chain (parents + itself) can be run for chosen stores get the store popup
        $storeScopedJobIds = $jobs->filter(fn ($job) => !empty($this->runner->scopedClasses($this->chainClasses($job))))
            ->pluck('id')
            ->all();

        return view('jobs.index', compact('jobs', 'storeScopedJobIds'));
    }

    /**
     * Stores the job's chain can be run for (JSON for the Run Now popup).
     */
    public function storeOptions(ScheduledJob $job)
    {
        return response()->json($this->runner->storeOptions($this->chainClasses($job)));
    }

    public function run(ScheduledJob $job, RunJobRequest $request)
    {
        $storeIds = $request->storeIds();
        $classes = $this->chainClasses($job);

        if ($storeIds !== null) {
            $this->runner->assertEligible($classes, $storeIds);
        }

        try {
            // create a bus of jobs: every parent first, then the job itself
            $arrayClasses = [];

            foreach ($classes as $jobClass) {
                Log::info('Job Class: ' . $jobClass);
                $instance = $this->runner->build($jobClass, $storeIds);

                // A store-scoped job none of the selected stores apply to is skipped, not run for every store
                if ($instance === null) {
                    Log::info('Skipping ' . $jobClass . ': none of the selected stores are eligible for it.');
                    continue;
                }

                $arrayClasses[] = $instance;
            }
            Log::info('All Jobs: ' . json_encode($arrayClasses));

            if (empty($arrayClasses)) {
                return back()->with('error', 'None of the jobs can run for the selected stores.');
            }

            Bus::chain($arrayClasses)->dispatch();

            $job->update([
                'last_run' => now(),
            ]);

            $message = 'Job queued successfully';
            if ($storeIds !== null) {
                $message .= ' for ' . implode(', ', $this->runner->storeNames($storeIds));
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Job start failed: ' . $e->getMessage(). ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine());
        }
    }

    public function edit(ScheduledJob $job)
    {
        $allJobs = ScheduledJob::all();
        $parents = ScheduleParentJob::where('job_id', $job->id)->pluck('parent_id')->toArray();
        return view('jobs.edit', compact('job', 'allJobs', 'parents'));
    }

    public function update(ScheduledJob $job, Request $request)
    {
        $parentsJobs = $request->input('parents', []);
        foreach ($parentsJobs as $parentJobId) {
            ScheduleParentJob::updateOrCreate(
                ['parent_id' => $parentJobId, 'job_id' => $job->id],
                ['parent_id' => $parentJobId, 'job_id' => $job->id]
            );
        }

        return redirect()->route('jobs.index')->with('success', 'Job updated successfully');
    }

    public function running()
    {
        $jobs = Job::all();
        return view('jobs.running', compact('jobs'));
    }

    /**
     * Class names in the order Run Now chains them: every parent, then the job itself.
     *
     * @return string[]
     */
    private function chainClasses(ScheduledJob $job): array
    {
        return $this->getAllParents($job->id)
            ->pluck('class_name')
            ->push($job->class_name)
            ->filter()
            ->values()
            ->all();
    }

    private function getAllParents(mixed $id)
    {
        // get all the parent and their parent and run all te jobs into one Bus
        $parents = ScheduleParentJob::where('job_id', $id)->get();
        Log::info('All Jobs: ' . json_encode($parents));
        $queue = new Queue();
        foreach ($parents as $parent) {
            $queue->enqueue($parent->parent_id);
        }
        Log::info('Queue: ' . $queue->printQueue());
        $allParents = [];
        while ($queue->size() > 0) {
            $nextId = $queue->peek();
            $queue->dequeue();
            $parents = ScheduleParentJob::where('job_id', $nextId)->get();
            $allParents[] = $nextId;
            foreach ($parents as $parent) {
                $queue->enqueue($parent->parent_id);
            }
        }
        Log::info('All Parents: ' . json_encode($allParents));
        // make it reverse order
        $allParents = array_reverse($allParents);
        return collect($allParents)->map(function ($parent) {
            return ScheduledJob::find($parent);
        })->filter(function ($job) {
            return $job !== null;
        });
    }
}
