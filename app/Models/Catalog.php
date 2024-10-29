<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    // Set a custom table name
    protected $table = 'products';

    // Custom primary key
    protected $primaryKey = 'product_id';


    protected $fillable = ['product_id','product_title', 'product_type', 'product_manufacturer', 'product_price', 'product_wholesale_price','product_marketplace_price', 'product_mrsp', 'dimensions_width', 'dimensions_height', 'dimensions_length', 'product_weight','product_weight_unit','product_package_weight','package_weight_unit','package_dimensions_width','package_dimensions_height','package_dimensions_length', 'product_sku','product_description','product_status','product_condition','target_aud','product_tags','product_color','product_qty','product_reorder_qty','product_pick_location','title_template','description_template'];
}
