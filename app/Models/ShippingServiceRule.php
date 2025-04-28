<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingServiceRule extends Model
{
    // Set a custom table name
    protected $table = 'shipping_service_rule';

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


    protected $fillable = ['id','min_weight_value','min_weight_weight_unit','max_weight_value','max_weight_weight_unit','priority','min_price','max_price','signature_confirmation','client_id','version','weight_operator','price_operator','round_weight_value','round_weight_weight_unit','package_type_filter_id','shipping_carrier_service_id','color','quantity_operator','min_quantity','max_quantity','created_by','updated_by','deleted_by','deleted_at','created_at','updated_at'];

    public function packageType(){
        return $this->belongsTo(ShippingPackage::class,'package_type_filter_id');
    }
}
