<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingCarrierPackage extends Model
{
    // Set a custom table name
    protected $table = 'shipping_carrier_package';

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


    protected $fillable = ['id','name','shipping_carrier_id','dimensions_length','dimensions_width','dimensions_height','dimensions_length_unit','max_weight_value','max_weight_weight_unit','non_machinable','created_at','updated_at'];

    public function shippingCarrier(){
        return $this->belongsTo(ShippingCarrier::class,'shipping_carrier_id');
    }

}
