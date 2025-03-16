<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    protected $table = 'product_size';
    protected $fillable = ['id', 'value_unit', 'value', 'short_code', 'created_at', 'updated_at'];
    public $timestamps = false;
}
