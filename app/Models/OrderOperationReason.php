<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderOperationReason extends Model
{
    // Set a custom table name
    protected $table = 'order_operation_reason';

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

    protected $fillable = ['store_type_id', 'operation_type', 'name', 'channel_code', 'created_at', 'updated_at'];
}
