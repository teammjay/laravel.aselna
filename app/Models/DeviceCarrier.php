<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeviceCarrier extends Model
{

    protected $table = 'device_carrier';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string)Str::uuid();
            }
        });
    }

    public $fillable = ['device_id','carrier_id', 'carrier_model_number', 'version', 'created_at', 'updated_at'];

    public function carrier()
    {
        return $this->belongsTo(Carrier::class, 'carrier_id','id');
    }
}
