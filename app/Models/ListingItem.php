<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingItem extends Model
{
    // Set a custom table name
    protected $table = 'listing_item';

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


    protected $fillable = ['carrier_id','model_name','brand_name','carrier_name','device_id','brand_id','device_series_id','device_type_id','device_type_name','device_code','device_rating_id','device_rating','listed_count','listed_type_count','priority','created_by'];
}
