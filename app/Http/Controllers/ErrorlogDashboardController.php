<?php

namespace App\Http\Controllers;

use App\Helpers\QueryFilters;
use App\Models\ErrorLog;
use App\Models\Store;
use Illuminate\Http\Request;

class ErrorlogDashboardController extends Controller
{
    public function index()
    {
        $stores = Store::with('storeType')->where('store_status','<>',0)->orderBy('name')->get();

        return view('errorlog_dashboard.list',compact('stores'));
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
        $query = ErrorLog::with('store','store.storeType')->select('*');

        // Filter by status
        QueryFilters::applyFilter($query, $request, 'operation', 'operation_operator', 'operation');

        QueryFilters::applyFilter($query, $request, 'stack_trace', 'stack_trace_operator', 'stack_trace');

        // Filter by tags
        QueryFilters::applyFilter($query, $request, 'message', 'message_operator', 'message');

        // Filter by tags
        QueryFilters::applyFilter($query, $request, 'store_type', 'store_type_operator', 'store_type');

        // Filter by created date
        if ($request->has('created_at') && $request->created_at != '') {
            $query->whereDate('created_at', date('Y-m-d',strtotime($request->created_at)));
        }

        // Filter by updated date
        if ($request->has('updated_at') && $request->updated_at != '') {
            $query->whereDate('updated_at', date('Y-m-d',strtotime($request->updated_at)));
        }

        // Filter by store
        if ($request->has('store_id') && $request->store_id != '') {
            $query->where('store_id', $request->store_id);
        }


        $totalRecords = (clone $query)->toBase()->getCountForPagination();
        $query->orderBy($columnName, $columnSortOrder);
        $errorlogs = $query->paginate($perPage, ['*'], 'page', floor($start/$perPage)+1);
        $totalRecordwithFilter = $totalRecords;

        /*echo '<pre>';
        print_r($errorlogs);
        echo '</pre>';die;*/

        $data = [];
        if (!empty((array)$errorlogs)) {
            foreach ($errorlogs as $error) {

                $operationType = explode('/',str_replace("\\","/",$error->operation));
                $operationType = preg_replace('/::handle$/', '', $operationType);
                $name = end($operationType);


                $store = $error->store->name ?? '';
                $storeType = $error->store->storeType->name ?? '';

                $data[] = array(
                    "store" => $store.'-'.$storeType  ?? '',
                    "created_at" => date('Y-m-d H:i:s',strtotime($error->created_at)),
                    "updated_at" => date('Y-m-d H:i:s',strtotime($error->updated_at)),
                    "error_count" => $error->error_count,
                    "error_level" => 'Error',
                    "operation" => $name,
                    "message" => $error->message,
                    "status" => 'Created',
                    "stack_trace" => ($error->stack_trace) ? '<i class="fa fa-exclamation-triangle show-error" style="font-size:24px" data-content="'.addslashes(str_replace('"', '',$error->stack_trace)).'"></i>' : "",
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

            $query = ErrorLog::select('*');

            switch($purge_opt){
                case 'older_30_days':
                    $query->where('updated_at', '<', \Carbon\Carbon::now()->subDays(30));
                    break;
                case 'older_7_days':
                    $query->where('updated_at', '<', \Carbon\Carbon::now()->subDays(7));
                    break;
                case 'custom_period':
                    $from_date = $request->input('from_date');
                    $to_date = $request->input('to_date');
                    $startDate = \Carbon\Carbon::parse($from_date);
                    $endDate   = \Carbon\Carbon::parse($to_date);

                    $datesToDelete = [$startDate, $endDate];

                    $query->whereIn(DB::raw('DATE(updated_at)'), $datesToDelete)->delete();

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