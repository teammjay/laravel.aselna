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


    protected $fillable = ['code','name','max_title_length','max_variation_name_length','max_listing_variants','max_random_title_length','acknowledge_order','min_image_width','min_image_height','order_shipment_control_backward_days','create_image_per_listing','created_by','updated_by'];

}
