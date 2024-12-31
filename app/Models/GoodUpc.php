<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodUpc extends Model
{
    protected $table = 'good_upc';
    protected $fillable = ['upc', 'is_used'];
}
