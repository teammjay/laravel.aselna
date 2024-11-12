<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreDeviceType extends Model
{
    // Set a custom table name
    protected $table = 'store_device_type';

    public $timestamps = false;

    protected $fillable = ['store_id','device_type_id'];
}
