<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingCarrierServiceChannelDetail extends Model
{
    protected $table = 'shipping_carrier_service_channel_detail';

    public $timestamps = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'carrier_service_id',
        'store_type_id',
        'code',
        'created_at',
        'updated_at'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string)Str::uuid();
            }
        });
    }

    public function shippingCarrierService()
    {
        return $this->belongsTo(ShippingCarrierService::class, 'shipping_carrier_service_id');
    }
}
