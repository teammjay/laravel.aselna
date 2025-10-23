<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingRequirement extends Model
{
    protected $fillable = [
        'category',
        'type',
        'table',
        'column',
        'key_column',
        'key_value',
        'value_column',
        'store_type_id',
    ];
}
