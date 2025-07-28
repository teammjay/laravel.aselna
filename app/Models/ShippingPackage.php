<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingPackage extends Model
{
    // Set a custom table name
    protected $table = 'shipping_package';

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


    protected $fillable = ['id','name','client','dimensions_length','dimensions_width','dimensions_height','dimensions_length_unit','weight_value','weight_weight_unit','created_at','updated_at'];

    public static function getDefaultShippingPackage(){

        $packageDetail = Self::whereLike('name','%Rectangular Package%')->first();
        return $packageDetail->id;
    }
}
