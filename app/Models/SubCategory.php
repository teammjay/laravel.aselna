<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    protected $table = 'categories_sub';

    protected $fillable = ['category_id', 'name', 'image', 'code', 'notes1', 'order', 'status', 'created_by', 'updated_by'];
}
