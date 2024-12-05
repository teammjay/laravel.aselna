<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreTypeCategories extends Model
{
    protected $table = 'store_type_categories';
    protected $fillable = ['store_type_id', 'category_id', 'parent_id', 'name', 'code' , 'id'];
}
