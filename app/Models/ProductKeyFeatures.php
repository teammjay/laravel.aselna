<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductKeyFeatures extends Model
{
    // Set a custom table name
    protected $table = 'product_key_features';


    protected $fillable = ['product_id','value', 'sort_order'];
}
