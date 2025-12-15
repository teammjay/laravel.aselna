<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTypeStoreTypeCATLink extends Model
{
    protected $table = 'product_type_store_type_category_link';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'product_type_id', 'store_type_category_id'];
}
