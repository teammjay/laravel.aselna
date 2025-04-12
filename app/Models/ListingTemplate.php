<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ListingTemplate extends Model
{
    // Set a custom table name
    protected $table = 'listing_template';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string)Str::uuid();
            }
        });
    }


    protected $fillable = ['id', 'name', 'client_id', 'code', 'created_by', 'updated_by','created_at', 'updated_at'];
}
