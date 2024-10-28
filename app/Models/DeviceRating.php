<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceRating extends Model
{
    protected $table = 'device_ratings';

    protected $fillable = ['value', 'created_by', 'updated_by', 'status'];

}
