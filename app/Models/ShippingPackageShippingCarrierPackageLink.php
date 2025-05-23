<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingPackageShippingCarrierPackageLink extends Model
{
    // Set a custom table name
    protected $table = 'shipping_package_shipping_carrier_package_link';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['shipping_package_id','shipping_carrier_package_id'];


    public function ShippingCarrierPackage()
    {
        return $this->belongsTo(ShippingCarrierPackage::class,'shipping_carrier_package_id');
    }

}
