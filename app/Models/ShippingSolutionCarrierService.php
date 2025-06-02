<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingSolutionCarrierService extends Model
{
    protected $table = 'shipping_solution_carrier_service';
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

    protected $fillable = [
        'id',
        'solution_code',
        'shipping_solution_id',
        'shipping_service_id',
        'solution_carrier_code',
        'created_at',
        'updated_at'
    ];
}
