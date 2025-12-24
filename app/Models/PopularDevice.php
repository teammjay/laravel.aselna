<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DeviceType;
use App\Models\Device;
use Illuminate\Support\Str;

class PopularDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'device_type_id',
        'device_id',
        'priority'
    ];

//    public function deviceType()
//    {
//        return $this->belongsTo(DeviceType::class, 'device_type_id');
//    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public $incrementing = false;
    protected $keyType = 'string';
    // table name
    protected $table = 'popular_devices';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
