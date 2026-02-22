<?php

namespace App\Http\Controllers;

use App\Models\JobStatus;
use App\Models\StoreType;
use Illuminate\Http\Request;
use App\Helpers\QueryFilters;
use Illuminate\Support\Facades\DB;

class QueueJobsController extends Controller
{
    public function index()
    {
        // Fetch all jobs, grouped by chain
        //$jobs = JobStatus::orderBy('created_at', 'desc')->get();
            //->groupBy('chain_id');

        $storeTypes = StoreType::orderBy('code')->get();

        return view('queue_jobs.list',compact('storeTypes'));
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

        // Filter by status
        QueryFilters::applyFilter($query, $request, 'status', 'status_operator', 'status');

        QueryFilters::applyFilter($query, $request, 'chain_id', 'chain_id_operator', 'chain_id');

        // Filter by tags
        QueryFilters::applyFilter($query, $request, 'tags', 'tags_operator', 'tags');

        // Filter by tags
        QueryFilters::applyFilter($query, $request, 'tags', 'tags_operator', 'store_type');

        // Filter by job type
        QueryFilters::applyFilter($query, $request, 'type', 'job_type_operator', 'job_type');

        // Filter by created date
        if ($request->has('created_at') && $request->created_at != '') {
            $query->whereDate('created_at', date('Y-m-d',strtotime($request->created_at)));
        }

        // Filter by started date
        if ($request->has('started_at') && $request->started_at != '') {
            $query->whereDate('started_at', date('Y-m-d',strtotime($request->started_at)));
        }

        // Filter by created date
        if ($request->has('finished_at') && $request->finished_at != '') {
            $query->whereDate('finished_at', date('Y-m-d',strtotime($request->finished_at)));
        }

        //$totalRecords = (clone $query)->toBase()->getCountForPagination();
        //$query->orderBy('created_at', 'desc');

        $totalRecords = (clone $query)
            ->select(DB::raw('COUNT(DISTINCT chain_id) as total'))
            ->value('total');

        //$jobs = $query->distinct()->select('chain_id', 'created_at')->paginate($perPage, ['*'], 'page', floor($start/$perPage)+1);
        $jobs = $query
            ->select('chain_id', DB::raw('MAX(created_at) as latest_created_at'))
            ->groupBy('chain_id')
            ->orderByDesc('latest_created_at')
            ->paginate($perPage, ['chain_id'], 'page', floor($start/$perPage)+1);

        $totalRecordwithFilter = $totalRecords;

        $data = [];
        if (!empty((array)$jobs)) {
            foreach ($jobs as $chainId) {
                $chainData = [];
                $chainJobs = JobStatus::where('chain_id', $chainId->chain_id)->orderBy('created_at', 'asc')->get();
                foreach ($chainJobs as $job) {

                    $jobType = explode('/', str_replace("\\", "/", $job->type));
                    $name = end($jobType);

                    $chainData[] = array(
                        "job_id" => $job->job_id,
                        "chain_id" => $job->chain_id,
                        "name" => $name,
                        "type" => $job->type,
                        "status" => '<span class="status-' . $job->status . '">' . ucfirst($job->status) . '</span>',
                        "queue" => $job->queue ?? '',
                        "started_at" => ($job->started_at) ? $job->started_at->diffForHumans() : "",
                        "finished_at" => $job->finished_at
                            ? $job->finished_at->diffForHumans() . " (" . round($job->started_at->diffInMinutes($job->finished_at)) . " minutes)"
                            : "",
                        "output" => ($job->output) ? '<i class="fa fa-exclamation-triangle show-error" style="font-size:24px" data-content="' . addslashes(str_replace('"', '', $job->output)) . '"></i>' : "",
                        "tags" => $job->tags,
                        "created_at" => date('Y-m-d H:i:s', strtotime($job->created_at))
                    );
                }
                if (count($chainData) > 1) {
                    $data[] = [
                        'is_chain' => true,
                        'parent_job' => $chainData[0],
                        // get all other jobs as child jobs
                        'child_job' => array_slice($chainData, 1)
                    ];
                } else {
                    $data[] = [
                        'is_chain' => false,
                        'parent_job' => $chainData[0],
                        'child_job' => null
                    ];
                }
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

    public function filterOld(Request $request)
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

        // Filter by status
        QueryFilters::applyFilter($query, $request, 'status', 'status_operator', 'status');

        QueryFilters::applyFilter($query, $request, 'chain_id', 'chain_id_operator', 'chain_id');

        // Filter by tags
        QueryFilters::applyFilter($query, $request, 'tags', 'tags_operator', 'tags');

        // Filter by tags
        QueryFilters::applyFilter($query, $request, 'tags', 'tags_operator', 'store_type');

        // Filter by job type
        QueryFilters::applyFilter($query, $request, 'type', 'job_type_operator', 'job_type');

        // Filter by created date
        if ($request->has('created_at') && $request->created_at != '') {
            $query->whereDate('created_at', date('Y-m-d',strtotime($request->created_at)));
        }

        // Filter by started date
        if ($request->has('started_at') && $request->started_at != '') {
            $query->whereDate('started_at', date('Y-m-d',strtotime($request->started_at)));
        }

        // Filter by created date
        if ($request->has('finished_at') && $request->finished_at != '') {
            $query->whereDate('finished_at', date('Y-m-d',strtotime($request->finished_at)));
        }

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
                    "finished_at" => $job->finished_at
                        ? $job->finished_at->diffForHumans() . " (" . round($job->started_at->diffInMinutes($job->finished_at)) . " minutes)"
                        : "",
                    "output" => ($job->output) ? '<i class="fa fa-exclamation-triangle show-error" style="font-size:24px" data-content="'.addslashes(str_replace('"', '',$job->output)).'"></i>' : "",
                    "tags" => $job->tags,
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

    public function purgeRecords(Request $request){

        $purge_opt = $request->input('purge_opt');
        if($purge_opt){

            $query = JobStatus::select('*');

            switch($purge_opt){
                case 'older_30_days':
                    $query->where('created_at', '<', \Carbon\Carbon::now()->subDays(30));
                    break;
                case 'older_7_days':
                    $query->where('created_at', '<', \Carbon\Carbon::now()->subDays(7));
                    break;
                case 'custom_period':
                    $from_date = $request->input('from_date');
                    $to_date = $request->input('to_date');
                    $startDate = \Carbon\Carbon::parse($from_date);
                    $endDate   = \Carbon\Carbon::parse($to_date);

                    $datesToDelete = [$startDate, $endDate];

                    $query->whereIn(DB::raw('DATE(created_at)'), $datesToDelete)->delete();

                    //$query->whereBetween('created_at', [$startDate, $endDate]);
                    break;
            }

            $query->delete();

            return response()->json(['success' => true, 'message' => 'Record purged successfully!']);
        }else{
            return response()->json(['success' => false, 'message' => 'Option not selected!']);
        }
    }
}
