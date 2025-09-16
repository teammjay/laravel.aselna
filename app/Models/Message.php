<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Message extends Model
{
    // Set a custom table name
    protected $table = 'hd_ticket';

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

    protected $fillable = ['id', 'ticket_type_id', 'ticket_email','username', 'status', 'related_order_id', 'subject', 'message_id','notes','customer_id','reference_code','related_order_operation_id','order_id','store_id','ticket_date','ticket_code','message_read','replies','last_message_date','important','customer_merge_status','ticket_user_id','sku','related_listing_item_id','created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'];

    public function store(){
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function ticketType(){
        return $this->belongsTo(HdTicketType::class, 'ticket_type_id');
    }

    public function customer(){
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function order(){
        return $this->belongsTo(Order::class, 'order_id','order_id');
    }
}
