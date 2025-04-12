<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ListingItemLink extends Model
{
    // Set a custom table name
    protected $table = 'listing_item_link';

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


    protected $fillable = ['id', 'item_id', 'listing_id', 'feed_id', 'parent_id', 'client', 'replaced_id', 'order_operation_item_id', 'old_price', 'sync_status', 'feed_message_id', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    public function listingItem()
    {
        return $this->belongsTo(ListingItem::class,'item_id');
    }

    public function item()
    {
        return $this->belongsTo(ListingItem::class);
    }

    public function feed()
    {
        return $this->belongsTo(ListingFeed::class);
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class,'listing_id');
    }
}
