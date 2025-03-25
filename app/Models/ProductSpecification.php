<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSpecification extends Model
{
    protected $table = 'product_specification';
    protected $fillable = ['id', 'name', 'value', 'product_id'];
    public $timestamps = false;
}
