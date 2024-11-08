<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTags extends Model
{
    // Set a custom table name
    protected $table = 'product_tag';


    protected $fillable = ['product_id','name'];
}
