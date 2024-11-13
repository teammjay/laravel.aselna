<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingCarrierService extends Model
{
    // Set a custom table name
    protected $table = 'shipping_carrier_service';

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


    protected $fillable = ['name','shipping_carrier_id','included_insurance','default_package_dimension_length','default_package_dimension_width','default_package_dimension_height','default_package_dimension_length_unit','generic_service_method','max_weight_value','max_weight_weight_unit','delivery_time','earliest_delivery_days','latest_delivery_days','service_level'];

    public function ShippingCarrier()
    {
        return $this->belongsTo(ShippingCarrier::class);
    }
}
