<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class productSpecificationInfoCategoryLink extends Model
{
    protected $table = 'product_specification_info_category_link';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['specification_info_id', 'product_type_id'];
}