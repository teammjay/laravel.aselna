<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeviceSubTypes extends Model
{
    //
    protected $table = 'device_sub_types';
    protected $fillable = ['name', 'notes1', 'device_type_id', 'created_by', 'updated_by', 'code', 'status'];

    public function deviceType()
    {
        return $this->belongsTo(DeviceTypes::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
