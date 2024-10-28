<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTitleKeyword extends Model
{
    // Set a custom table name
    protected $table = 'product_title_keyword';


    protected $fillable = ['product_id','keyword', 'priority'];
}
