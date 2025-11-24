<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderItemAttribute extends Model
{
    protected $table = 'order_item_attribute';

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


    protected $fillable = ['key', 'value', 'order_item_id','created_by', 'updated_by','deleted_by','created_at', 'updated_at','deleted_at'];
}
