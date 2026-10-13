<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunJobRequest;
use App\Http\Requests\ScheduledJobsRequest;
use App\Models\ScheduledJobs;
use App\Models\Store;
use App\Models\StoreScheduledCronJobs;
use App\Models\StoreType;
use App\Services\Jobs\StoreScopedJobRunner;
use Illuminate\Http\Request;

class ScheduledjobsController extends Controller
{
    public function __construct(private StoreScopedJobRunner $runner)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $scheduled_jobs = ScheduledJobs::all();

        // Jobs that can be run for chosen stores get the store popup on Run Now (JobRunMode::STORE_PICKER);
        // commands and callbacks always run as configured
        $runModes = $scheduled_jobs->mapWithKeys(fn ($job) => [
            $job->id => $this->runner->runMode(($job->type ?? 'job') === 'job' ? [$job->class_name] : []),
        ])->all();

        return view('scheduled_jobs.index', compact('scheduled_jobs', 'runModes'));
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
        $storeTypeIds = StoreScheduledCronJobs::where('scheduled_job_id',$id)
                        ->groupBy('store_type_id')
                        ->distinct()
                        ->pluck('store_type_id')
                        ->toArray();
        return view('scheduled_jobs.add', compact('scheduled_job','storeTypes','storeTypeIds'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ScheduledJobsRequest $request, string $id)
    {
        $request->validated();

        $request['is_active'] = $request->has('is_active') ? 1 : 0;

        ScheduledJobs::findOrFail($id)->update($request->all());

        // Always reset the mapping, so clearing every store type also clears the stores
        StoreScheduledCronJobs::where([
            'scheduled_job_id' => $id
        ])->delete();

        if($request->has('store_type_id')){

            foreach($request->store_type_id as $store_type_id){
                $stores = $request->store_id;

                if($stores){
                    foreach($stores as $store_id){
                        $storeDetail = Store::with('storeType')->where('id', $store_id)->first();
                        StoreScheduledCronJobs::updateOrCreate([
                            'scheduled_job_id' => $id,
                            'store_id' => $store_id,
                            'store_type_id' => $storeDetail->storeType->id
                        ],
                            [
                                'scheduled_job_id' => $id,
                                'store_id' => $store_id,
                                'store_type_id' => $storeDetail->storeType->id
                            ]
                        );
                    }
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

    public function run(ScheduledJobs $job, RunJobRequest $request)
    {
        $class = $job->class_name;

        if (empty($class) || !class_exists($class)) {
            return back()->with('error', "Class not found: {$class}");
        }

        $storeIds = $request->storeIds();

        if ($storeIds !== null) {
            $this->runner->assertEligible([$class], $storeIds);
        }

        // Pass parameters into the constructor the same way the scheduler does (routes/console.php).
        // For a store-scoped job a store selection replaces the first of them (the store argument).
        $params = json_decode($job->parameters ?? '', true);
        $params = is_array($params) ? array_values($params) : [];

        $instance = $this->runner->build($class, $storeIds, $params);

        if ($instance === null) {
            return back()->with('error', "{$job->name} cannot run for the selected stores.");
        }

        dispatch($instance);

        $message = "{$job->name} dispatched";
        if ($storeIds !== null) {
            $message .= ' for ' . implode(', ', $this->runner->storeNames($storeIds));
        }

        return back()->with('success', $message);
    }

    /**
     * Stores the job can be run for (JSON for the Run Now popup).
     */
    public function storeOptions(ScheduledJobs $job)
    {
        $classes = ($job->type ?? 'job') === 'job' && !empty($job->class_name) ? [$job->class_name] : [];

        return response()->json($this->runner->storeOptions($classes));
    }

    public function getStore(Request $request){

        $store_type_ids = $request->input('store_type_id');
        $job_id = $request->input('job_id');
        $storeOption = '';
        $storeIds = [];
        if($store_type_ids){
            if($job_id){
                $storeIds = StoreScheduledCronJobs::where('scheduled_job_id',$job_id)->get()->pluck('store_id')->toArray();
                //print_r($storeIds);die;
            }

            $stores = Store::with('storeType')
                ->leftJoin('store_type', 'store.store_type_id', '=', 'store_type.id')
                ->whereIn('store.store_type_id', $store_type_ids)
                ->where('store.store_status', '<>', 0)
                ->orderBy('store_type.name')
                ->orderBy('store.name')
                ->select('store.*')
                ->get();

            if($stores){
                foreach($stores as $store){
                    if(in_array($store->id,$storeIds)){
                        $sel = 'checked';
                    }else{
                        $sel = '';
                    }
                    //$storeOption .= '<input type="checkbox" name="store_id[]" value="'.$store->id.'" '.$sel.'/> '.$store->name.'<br>';

                    $storeOption .= '<tr class="store_row" id="store_'.$store->id.'" data-id="'.$store->id.'">
                                        <td>'.$store->name.' - '.$store->storeType->name.'</td>
                                        <td align="center">
                                            <input type="checkbox" name="store_id[]" value="'.$store->id.'" '.$sel.'/>
                                        </td>
                                    </tr>';
                }
            }
        }

        return response()->json([
            'success' => true,
            'options' => $storeOption
        ]);

    }
}
