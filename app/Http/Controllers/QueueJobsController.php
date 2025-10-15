<?php

namespace App\Http\Controllers;

use App\Models\JobStatus;
use Illuminate\Http\Request;

class QueueJobsController extends Controller
{
    public function index()
    {
        // Fetch all jobs, grouped by chain
        $jobs = JobStatus::orderBy('created_at', 'desc')
            ->get();
            //->groupBy('chain_id');

        return view('queue_jobs.list', ['jobs' => $jobs]);
    }
}
