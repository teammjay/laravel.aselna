<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImages extends Model
{
    // Set a custom table name
    protected $table = 'product_images';


    protected $fillable = ['product_id','image', 'thumbnail_image','sort_order'];
}
