<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeviceTypes extends Model
{
    //
    protected $table = 'device_types';
    protected $fillable = ['id', 'name', 'notes1', 'created_by', 'updated_by', 'code', 'status'];

    // timestamps
    public $timestamps = true;

    // add id as uuid
    protected $primaryKey = 'id';
    protected $keyType = 'string';

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
