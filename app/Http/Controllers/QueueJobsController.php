<?php

namespace App\Http\Controllers;

use App\Models\JobStatus;
use Illuminate\Http\Request;

class QueueJobsController extends Controller
{
    public function index()
    {
        // Fetch all jobs, grouped by chain
        //$jobs = JobStatus::orderBy('created_at', 'desc')->get();
            //->groupBy('chain_id');

        return view('queue_jobs.list');
    }

    public function filter(Request $request)
    {

        //DB::enableQueryLog();
        $draw = $request['draw'];
        $start = $request['start'];
        $perPage = $request['length'];
        $columnIndex = $request['order'][0]['column'];
        $columnName = $request['columns'][$columnIndex]['data'];
        $columnSortOrder = $request['order'][0]['dir'];

        // add select
        $query = JobStatus::select('*');

        $totalRecords = (clone $query)->toBase()->getCountForPagination();
        $query->orderBy($columnName, $columnSortOrder);
        $jobs = $query->paginate($perPage, ['*'], 'page', floor($start/$perPage)+1);
        $totalRecordwithFilter = $totalRecords;

        $data = [];
        if (!empty((array)$jobs)) {
            foreach ($jobs as $job) {

                $jobType = explode('/',str_replace("\\","/",$job->type));
                $name = end($jobType);

                $data[] = array(
                    "job_id" => $job->job_id,
                    "chain_id" => $job->chain_id,
                    "name" => $name,
                    "type" => $job->type,
                    "status" => '<span class="status-'.$job->status.'">'.ucfirst($job->status).'</span>',
                    "queue" => $job->queue ?? '',
                    "started_at" => ($job->started_at) ? $job->started_at->diffForHumans() : "",
                    "finished_at" => ($job->finished_at) ? $job->finished_at->diffForHumans() : "",
                    "output" => ($job->output) ? '<i class="fa fa-exclamation-triangle" style="font-size:24px" title="'.$job->output.'"></i>' : "",
                    "created_at" => date('Y-m-d H:i:s',strtotime($job->created_at))
                );
            }
        }

        $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecordwithFilter,
            "iTotalDisplayRecords" => $totalRecordwithFilter,
            "aaData" => $data
        );

        return $response;
    }
}
