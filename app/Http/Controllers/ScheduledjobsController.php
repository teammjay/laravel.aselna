<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduledJobsRequest;
use App\Models\ScheduledJobs;
use Illuminate\Http\Request;

class ScheduledjobsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $scheduled_jobs = ScheduledJobs::all();
        return view('scheduled_jobs.index', compact('scheduled_jobs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('scheduled_jobs.add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ScheduledJobsRequest $request)
    {
        $request->validated();

        $request['is_active'] = $request->has('is_active') ? 1 : 0;

        ScheduledJobs::create($request->all());

        return redirect()->route('scheduled_jobs.index')
            ->with('success', 'Cron Job created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $scheduled_job = ScheduledJobs::findOrFail($id);
        return view('scheduled_jobs.add', compact('scheduled_job'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ScheduledJobsRequest $request, string $id)
    {
        $request->validated();

        $request['is_active'] = $request->has('is_active') ? 1 : 0;

        ScheduledJobs::findOrFail($id)->update($request->all());

        return redirect()->route('scheduled_jobs.index')
            ->with('success', 'Cron Job updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        ScheduledJobs::findOrFail($id)->delete();

        return redirect()->route('scheduled_jobs.index')
            ->with('success', 'Cron Job deleted successfully.');
    }
}
