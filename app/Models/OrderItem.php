<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    protected $table = 'order_item';

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


    protected $fillable = ['title', 'order_id', 'product_id', 'client_id', 'listing_item_id', 'sku', 'store_sku', 'quantity', 'id1', 'id2', 'item_price', 'image_id', 'thumbnail_id', 'shipping_price', 'item_tax', 'shipping_tax', 'shipped_quantity', 'canceled_quantity', 'refund_quantity', 'refund_price', 'refund_tax_price', 'refund_shipping_price', 'refund_shipping_tax', 'acknowledged_quantity', 'replaced_quantity', 'return_request_quantity', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    public function order()
    {
        return $this->belongsTo(Order::class,'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Catalog::class,'product_id')->where(function ($q) {
            $q->whereIn('product_status', ['active','temporaryDisabled']);
        });
    }

    public function orderOperationItem()
    {
        return $this->hasOne(OrderOperationItem::class,'id');
    }

    public function listingItem()
    {
        return $this->belongsTo(ListingItem::class,'sku','sku');
    }

    public function listingItemViaId()
    {
        return $this->belongsTo(ListingItem::class,'listing_item_id','id');
    }

    public function orderItemAttribute()
    {
        return $this->hasMany(OrderItemAttribute::class,'id');
    }
}
