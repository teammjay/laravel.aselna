<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceTypes extends Model
{
    //
    protected $table = 'device_types';
    protected $fillable = ['name', 'notes1'];
}
