<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSpecification extends Model
{
    protected $table = 'product_specification';
    protected $fillable = ['id', 'name', 'created_at', 'updated_at'];
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}
