<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $table = 'order';

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

    protected $fillable = ['id','order_id','store_id','order_date','order_status','buyer_first_name','buyer_last_name','buyer_email','order_total','shipping_address_id','client_id','purchase_order_id','order_synchronization_id','status_message','number_of_sync_attempts','customer_id','order_operation_id','hd_ticket_id','buyer_user_id','order_risk_score','order_risk_message','contingent','requested_shipment_service','requested_shipment_carrier','created_by','updated_by','created_at','updated_at'];

    public function store()
    {
        return $this->belongsTo(Store::class,'store_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class,'customer_id');
    }

    public function customerAddress()
    {
        return $this->belongsTo(CustomerAddress::class,'shipping_address_id');
    }

    public function orderItem()
    {
        return $this->hasMany(OrderItem::class,'order_id');
    }

    public function orderOperation()
    {
        return $this->hasOne(OrderOperation::class,'id');
    }

}
