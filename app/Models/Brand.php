<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = ['name', 'notes1', 'notes2', 'notes3', 'order_no'];
}
