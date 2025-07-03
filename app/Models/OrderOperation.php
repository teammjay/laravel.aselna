<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderOperation extends Model
{
    // Set a custom table name
    protected $table = 'order_operation';

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

    protected $fillable = ['tracking_number','client_id','ship_date','cost','synchronized_with_store','insurance_value','shipment_batch_id','to_address_id','sync_status','sync_message','status','status_message','own_packaging_dimensions_length','own_packaging_dimensions_width','own_packaging_dimensions_height','own_packaging_dimensions_length_unit','signature_confirmation','shipping_package_id','weight_value','weight_weight_unit','ship_from_id','store_id','operation_type','expected_delivery_date','expected_shipment_date','order_synchronization_id','reference_id','reference_id2','completed_date','refund_without_return','order_id','label_creation_type','rma_number','return_charge_reject_reason_id','accept_return_charge','return_operation_id','shipping_carrier_id','shipping_carrier_service_id','hd_ticket_id','buyer_initiated','shipping_transaction_id','shipping_account_transaction_id','print_to_file','previous_operation_type','available_actions','seller_provides_return_label','products_rubber_stamp','created_by','updated_by','deleted_by','deleted_at','created_at','updated_at'];

    public function operationItem(){

        return $this->hasMany(OrderOperationItem::class);
    }

    public function order(){

        return $this->belongsTo(Order::class,'order_id');
    }

    public function carrier(){
        return $this->belongsTo(ShippingCarrier::class,'shipping_carrier_id');
    }

    public function carrierService(){
        return $this->belongsTo(ShippingCarrierService::class,'shipping_carrier_service_id');
    }

    public function shippingPackage()
    {
        return $this->belongsTo(ShippingPackage::class,'shipping_package_id');
    }

    public function shipFrom(){
        return $this->belongsTo(LogisticCenter::class,'ship_from_id','id');
    }

    public function store(){

        return $this->belongsTo(Store::class,'store_id');
    }

}
