<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductColor extends Model
{
    // Set a custom table name
    protected $table = 'product_color';

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

    protected $fillable = ['id', 'code', 'name', 'product_type_id', 'client_id', 'short_code', 'channel_mapping_id', 'position', 'created_by', 'updated_by', 'created_at', 'updated_at'];
}
