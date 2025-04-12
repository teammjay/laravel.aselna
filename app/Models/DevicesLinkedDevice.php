<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevicesLinkedDevice extends Model
{
    protected $table = 'device_series_linked_devices';
    protected $fillable = ['device_id', 'device_series_id'];

    public $timestamps = false;
    public $incrementing = false;

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id');
    }

    public function deviceSeries()
    {
        return $this->belongsTo(DeviceSeries::class, 'device_series_id');
    }
}
