<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ListingGenerationType extends Model
{
    // Set a custom table name
    protected $table = 'listing_generation_type';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = ['code','name','singleton','allow_duplicates','popular_device','include_carrier','include_device','carrier_combo','brand_combo','created_by','updated_by','created_at','updated_at'];
}
