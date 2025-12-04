<?php

namespace App\Http\Controllers;

use App\Http\Requests\ScheduledJobsRequest;
use App\Models\ScheduledJobs;
use App\Models\Store;
use App\Models\StoreScheduledCronJobs;
use App\Models\StoreType;
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
        $storeTypes = StoreType::orderBy('name')->get();
        return view('scheduled_jobs.add',compact('storeTypes'));
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
        $storeTypes = StoreType::orderBy('name')->get();
        return view('scheduled_jobs.add', compact('scheduled_job','storeTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ScheduledJobsRequest $request, string $id)
    {
        $request->validated();

        $request['is_active'] = $request->has('is_active') ? 1 : 0;

        ScheduledJobs::findOrFail($id)->update($request->all());

        if($request->has('store_type_id')){
            $stores = $request->store_id;

            StoreScheduledCronJobs::where([
                'scheduled_job_id' => $id,
                'store_type_id' => $request->store_type_id
            ])->delete();

            if($stores){
                foreach($stores as $store_id){

                    StoreScheduledCronJobs::updateOrCreate([
                        'scheduled_job_id' => $id,
                        'store_id' => $store_id,
                        'store_type_id' => $request->store_type_id
                    ],
                        [
                            'scheduled_job_id' => $id,
                            'store_id' => $store_id,
                            'store_type_id' => $request->store_type_id
                        ]
                    );
                }
            }

        }

        return redirect()->route('scheduled_jobs.edit',$id)
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

    public function run(ScheduledJobs $job)
    {
        $class = $job->class_name;

        if (!class_exists($class)) {
            return back()->with('error', "Class not found: {$class}");
        }

        // Pass parameters into the constructor
        $params = array_values($job->parameters ?? []);

        dispatch(new $class($params));

        return back()->with('success', "{$job->name} dispatched");
    }

    public function getStore(Request $request){

        $store_type_id = $request->input('store_type_id');
        $job_id = $request->input('job_id');
        $storeOption = '';
        $storeIds = [];
        if($store_type_id){
            if($job_id){
                $storeIds = StoreScheduledCronJobs::where('scheduled_job_id',$job_id)->get()->pluck('store_id')->toArray();
                //print_r($storeIds);die;
            }
            $stores = Store::where('store_type_id',$store_type_id)->where('store_status','<>', 0)->get();

            if($stores){
                foreach($stores as $store){
                    if(in_array($store->id,$storeIds)){
                        $sel = 'checked';
                    }else{
                        $sel = '';
                    }
                    $storeOption .= '<input type="checkbox" name="store_id[]" value="'.$store->id.'" '.$sel.'/> '.$store->name.'<br>';
                }
            }
        }

        return response()->json([
            'success' => true,
            'options' => $storeOption
        ]);

    }
}
