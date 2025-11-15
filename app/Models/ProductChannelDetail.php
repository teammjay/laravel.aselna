<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductChannelDetail extends Model
{
    protected $table = 'product_channel_detail';

    public $timestamps = false;

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

    protected $fillable = ['id', 'store_type_id', 'product_id', 'status', 'created_by', 'updated_by'];
}
