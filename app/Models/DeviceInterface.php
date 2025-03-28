<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeviceInterface extends Model
{
    protected $table = 'device_interface';

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

    protected $fillable = ['device_id','interface_id','created_by','updated_by','id','created_at','updated_at'];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function interfaces()
    {
        return $this->belongsTo(Interfaces::class, 'interface_id','id');
    }
}
