<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeviceStoreTypes extends Model
{
    protected $table = 'device_store_types';

    public $timestamps = false;

    protected $fillable = ['id', 'device_id', 'store_type_id', 'status'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string)Str::uuid();
            }
        });
    }
}
