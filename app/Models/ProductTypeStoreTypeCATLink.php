<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTypeStoreTypeCATLink extends Model
{
    public $table = 'product_type_store_type_category_link';

    public $keyType = 'string';

    public $incrementing = false;

    public $fillable = ['id', 'product_type_id', 'store_type_category_id'];
}
