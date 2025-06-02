<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    // Set a custom table name
    protected $table = 'client';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'name', 'simple_product_code_generation_template', 'created_by', 'updated_by', 'deleted_by', 'deleted_at', 'created_at', 'updated_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string)\Illuminate\Support\Str::uuid();
            }
        });
    }
}
