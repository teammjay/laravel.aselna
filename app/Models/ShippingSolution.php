<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingSolution extends Model
{
    // Set a custom table name
    protected $table = 'shipping_solution';

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


    protected $fillable = ['id','code','name','production_service_url','sandbox_service_url','shipment_estimate_available'];
}
