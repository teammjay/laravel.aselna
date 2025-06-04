<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HDMessage extends Model
{
    // Set a custom table name
    protected $table = 'hd_message';

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

    protected $fillable = ['id','ticket_type_id','ticket_email','status','subject','message_id','notes','reference_code','related_order_operation_id','order_id','store_id','ticket_date','ticket_code','message_read','replies','last_message_date','important','customer_merge_status','ticket_user_id','sku','related_listing_item_id','created_by','updated_by','deleted_by','deleted_at','created_at','updated_at'];
}
