<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingSolutionCarrierPackage extends Model
{
    protected $table = 'shipping_solution_carrier_package';
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
        'carrier_package_id',
        'label_size',
        'created_at',
        'updated_at',
    ];
}
