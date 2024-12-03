<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingCarrier extends Model
{
    // Set a custom table name
    protected $table = 'shipping_carrier';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }


    protected $fillable = ['code','name','created_at','updated_at'];

    public function ShippingCarrierService()
    {
        return $this->hasOne(ShippingCarrierService::class);
    }
}
