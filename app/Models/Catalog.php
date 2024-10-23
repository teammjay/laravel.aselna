<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    // Set a custom table name
    protected $table = 'products';

    // Custom primary key
    protected $primaryKey = 'product_id';


    protected $fillable = ['product_id','product_title', 'product_type', 'product_manufacturer', 'product_price', 'product_sku','product_description','product_status','product_stock_quantity','product_condition'];
}
