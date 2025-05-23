<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderOperationItem extends Model
{
    // Set a custom table name
    protected $table = 'order_operation_item';

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

    protected $fillable = ['order_item_id', 'order_operation_id', 'client_id', 'sync_status', 'sync_message', 'quantity', 'creation_retry_count', 'returned_quantity', 'item_price', 'shipping_price', 'item_tax_price', 'shipping_tax_price', 'reason_id', 'notes', 'refund_feedback_id', 'return_operation_item_id', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    public function orderOperation()
    {
        return $this->belongsTo(OrderOperation::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
