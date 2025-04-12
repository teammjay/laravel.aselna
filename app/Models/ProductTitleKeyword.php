<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductTitleKeyword extends Model
{
    // Set a custom table name
    protected $table = 'product_title_keyword';

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


    protected $fillable = ['id','product_id','keyword', 'priority','created_by','updated_by','created_at','updated_at'];
}
