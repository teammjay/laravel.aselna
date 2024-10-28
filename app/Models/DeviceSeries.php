<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceSeries extends Model
{
    protected $table = 'device_series';

    protected $fillable = ['name', 'slug', 'created_by', 'updated_by', 'status'];


}
