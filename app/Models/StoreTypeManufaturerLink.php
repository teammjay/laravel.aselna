<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreTypeManufaturerLink extends Model
{
    protected $table = 'store_type_manufaturer_links';
    public $timestamps = false;
    protected $fillable = ['id', 'store_type_id', 'manufacturer_id', 'manufacturer_name', 'manufacturer_code'];
    public $keyType = 'string';
}
