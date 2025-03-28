<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\ScheduledJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

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
            dispatch(new ($job->class_name)());

            $job->update([
                'last_run' => now(),
            ]);

            return back()->with('success', 'Job queued successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Job start failed: ' . $e->getMessage());
        }
    }

    public function running()
    {
        $jobs = Job::all();
        return view('jobs.running', compact('jobs'));
    }
}
