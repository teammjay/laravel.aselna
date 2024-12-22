<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Listing extends Model
{
    // Set a custom table name
    protected $table = 'listing';

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


    protected $fillable = ['id','store_id','generation_type_id','device_id','feed_type','client','status','chained','include_carriers','brand_combo','carrier_combo','preserve_title_description','manufacturer_id','feed_execution_delay','created_by','updated_by','created_at','updated_at'];
}
