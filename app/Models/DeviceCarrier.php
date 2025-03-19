<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceCarrier extends Model
{

    public $fillable = ['id','device_id','carrier_id', 'name', 'description', 'status', 'created_at', 'updated_at'];

    public $table = 'device_carrier';
}
