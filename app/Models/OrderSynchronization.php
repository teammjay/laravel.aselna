<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderSynchronization extends Model
{
    // Set a custom table name
    protected $table = 'order_synchronization';

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

    protected $fillable = ['store_id','number_of_items','synchronization_type','filter_start_date','filter_end_date','last_order_update_date','created_by','updated_by','deleted_by','deleted_at','created_at','updated_at'];

}
