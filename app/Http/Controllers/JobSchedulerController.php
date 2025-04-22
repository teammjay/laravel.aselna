<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\ScheduledJob;
use App\Models\ScheduleParentJob;
use App\Utils\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class JobSchedulerController extends Controller
{
    public function index()
    {
        $jobs = ScheduledJob::all();
        return view('jobs.index', compact('jobs'));
    }

    public function run(ScheduledJob $job)
    {
        try {

            $allJobs = $this->getAllParents($job->id);
            // create a bus of jobs

            $arrayClasses = [];

            foreach ($allJobs as $parentJob) {
                $jobClass = $parentJob->class_name;
                Log::info('Job Class: ' . $jobClass);
                $arrayClasses[] = new $jobClass();
            }
            Log::info('Job Class: ' . json_encode($arrayClasses));
            $arrayClasses[] = new $job->class_name();
            Log::info('All Jobs: ' . json_encode($arrayClasses));

            Bus::chain($arrayClasses)->dispatch();

            $job->update([
                'last_run' => now(),
            ]);

            return back()->with('success', 'Job queued successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Job start failed: ' . $e->getMessage());
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
