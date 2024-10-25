<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceSubTypes extends Model
{
    //
    protected $table = 'device_sub_types';
    protected $fillable = ['name', 'notes1', 'device_type_id'];

    public function deviceType()
    {
        return $this->belongsTo(DeviceTypes::class);
    }

}
