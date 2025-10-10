<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeviceSeries extends Model
{
    protected $table = 'device_series';

    protected $fillable = ['id','name', 'slug', 'created_by', 'updated_by', 'status'];

    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function linkedDevices()
    {
        return $this->hasMany(DevicesLinkedDevice::class, 'device_series_id');
    }

    public function devices()
    {
        return $this->belongsToMany(Device::class, 'device_series_linked_devices', 'device_series_id', 'device_id');
    }
}
