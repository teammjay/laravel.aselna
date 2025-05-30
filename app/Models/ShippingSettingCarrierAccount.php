<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingSettingCarrierAccount extends Model
{
    protected $table = 'shipping_setting_carrier_account';
    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'id',
        'shipping_carrier_id',
        'shipping_account_id',
    ];

    public function shippingCarrier()
    {
        return $this->belongsTo(ShippingCarrier::class, 'shipping_carrier_id');
    }

    public function shippingAccount()
    {
        return $this->belongsTo(ShippingAccount::class, 'shipping_account_id');
    }
}
