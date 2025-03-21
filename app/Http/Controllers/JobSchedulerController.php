<?php

namespace App\Http\Controllers;

use App\Models\ScheduledJob;
use App\Models\JobExecution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

class JobSchedulerController extends Controller
{
    public function index()
    {
        $jobs = ScheduledJob::with('executions')->get();
        return view('jobs.index', compact('jobs'));
    }

    public function run(ScheduledJob $job)
    {
        $execution = $job->executions()->create([
            'status' => 'pending',
            'started_at' => now(),
        ]);

        try {
            dispatch(new ($job->name)());
            $execution->update([
                'status' => 'queued',
                'output' => 'Job added to queue',
            ]);

            $job->update([
                'status' => 'queued',
                'last_run' => now(),
                'next_run' => $job->schedule ? now()->add($job->schedule) : null
            ]);

            return back()->with('success', 'Job queued successfully');
        } catch (\Exception $e) {
            $execution->update([
                'status' => 'failed',
                'finished_at' => now(),
                'output' => $e->getMessage()
            ]);
            return back()->with('error', 'Job failed to queue');
        }
    }
}
