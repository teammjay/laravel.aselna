<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductSpecification extends Model
{
    protected $table = 'product_specification';

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
    protected $fillable = ['id', 'name', 'product_id', 'created_at', 'updated_at','value'];
}
