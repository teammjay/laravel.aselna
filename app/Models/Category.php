<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

    protected $fillable = ['name', 'code', 'notes1', 'order', 'status', 'created_by', 'updated_by'];
}
