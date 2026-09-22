<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ListingItemProductImage extends Model
{
    protected $table = 'listing_item_product_images';

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

    protected $fillable = [
        'id',
        'listing_item_id',
        'product_image_id',
        'alt_product_image_id',
        'main_image',
        'version_number',
        'image_file_system_id',
        'path',
        'reference_id',
    ];

    public function listingItem()
    {
        return $this->belongsTo(ListingItem::class, 'listing_item_id', 'id');
    }

    public function productImage()
    {
        if ($this->product_image_id) {
            return $this->belongsTo(ProductImages::class, 'product_image_id');
        }

        return $this->belongsTo(ProductImageAlternative::class, 'alt_product_image_id');
    }
//
//    public function imageFileSystem()
//    {
//        return $this->belongsTo(ImageFileSystem::class, 'image_file_system_id', 'id');
//    }
}
