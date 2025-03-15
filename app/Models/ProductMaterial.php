<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMaterial extends Model
{
    protected $table = 'product_material';
    protected $fillable = ['id', 'name', 'created_at', 'updated_at'];
    public $timestamps = false;
}
