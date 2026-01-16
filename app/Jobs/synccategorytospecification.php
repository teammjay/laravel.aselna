<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationInfo;
use App\Models\productSpecificationInfoCategoryLink;
use App\Models\ProductType;
use App\Models\StoreType;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class synccategorytospecification implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 3000;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $store_types = StoreType::all();
            if (!empty($store_types)) {
                $tags = [];
                foreach ($store_types as $store_type) {
                    $store_type_id = $store_type->id;
                    //$store_type_id = '9ea83e8c-5b36-d377-d583-f7560b67f6b0';
                    $specifications = ProductSpecification::where('store_type_id', $store_type_id)->limit(500)->get();
                    /*echo '<pre>';
                    echo $store_type->id;
                    print_r($specifications);
                    echo '</pre>';*/
                    $tags[] = $store_type;

                    if (count($specifications ?? []) > 0) {
                        foreach ($specifications as $key => $spec) {
                            Log::info('Key:' . $key);
                            $product_id = $spec->product_id;
                            Log::info('Product ID:' . $product_id);
                            $name = $spec->name;
                            Log::info('Name:' . $name);
                            $store_type_id = $spec->store_type_id;
                            Log::info('Store Type:' . $store_type_id);
                            if ($product_id == 'd71c78b0-7b44-4cd4-863f-4e686d2be24a') continue;
                            $product_detail = Catalog::findOrFail($product_id);
                            Log::info('Product Detail:' . $product_detail);

                            if (!empty($product_detail)) {
                                $info_category = ProductType::with('children')->whereIn('parent_id', function ($subquery) {
                                    $subquery->select('id')->from('product_type')->where('code', 'cea');
                                })->orderBy('name', 'asc')->get();
                                $info_category = $info_category->pluck('id')->values()->all();

                                $category = ProductType::with('ancestors')->find($product_detail->product_type);
                                $category_id = $this->getAncestors($category, $info_category);

                                $specificationInfo = ProductSpecificationInfo::where('store_type_id', $store_type_id)->where('field_local_name', $name)->first();


                                if (!empty($specificationInfo) && $category_id != '') {
                                    $data = array(
                                        'specification_info_id' => $specificationInfo->id,
                                        'product_type_id' => $category_id
                                    );

                                    Log::info('CateItem:' . json_encode($data));

                                    productSpecificationInfoCategoryLink::updateOrCreate([
                                        'specification_info_id' => $specificationInfo->id,
                                        'product_type_id' => $category_id,
                                    ], $data);
                                } else {
                                    Log::info('else');
                                }

                                Log::info('record done');
                            }
                        }

                    }
                }

                $this->updateTrackingRecord(['tags' => implode(' ', $tags) . ' Category to Specification']);
            }
        }catch (\Throwable $e) {

            CriticalErrorService::report('Sync Category To Specification Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());

        }
    }

    private function getAncestors($category,$info_category)
    {
        if ($category->parent) {
            //echo $category->parent->name . "\n";
            if(in_array($category->parent->id, $info_category)){
                return $category->parent->id;
            }
            $this->getAncestors($category->parent,$info_category);
        }
    }
}
