<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreType extends Model
{
    // Set a custom table name
    protected $table = 'store_type';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }


    protected $fillable = ['id','code','name','max_title_length','max_variation_name_length','max_listing_variants','max_random_title_length','acknowledge_order','min_image_width','min_image_height','order_shipment_control_backward_days','create_image_per_listing','created_by','updated_by'];

    public static function getStoreTypeDetailById($store_id){

        $query1 = self::select('store_type.*');
        $query1->leftJoin('store','store.store_type_id', '=', 'store_type.id');
        $query1->where('store.id', $store_id);
        return $query1->first();

    }

    public function store()
    {
        return $this->belongsTo(Store::class,'store_type_id');
    }

    public function StoreTypeCategories()
    {
        return $this->hasMany(StoreTypeCategory::class,'id','store_type_id');
    }

    public function storetypecategory()
    {
        return $this->hasOne(StoreTypeCategory::class,'id');
    }

}
