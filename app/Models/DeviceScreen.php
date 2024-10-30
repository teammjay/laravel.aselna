<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceScreen extends Model
{

    protected $table = 'device_screens';
    protected $fillable = ['size', 'status', 'created_by', 'updated_by'];
}
