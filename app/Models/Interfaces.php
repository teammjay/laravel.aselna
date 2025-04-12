<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Interfaces extends Model
{
    protected $table = 'interface';

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


    protected $fillable = ['id', 'name', 'product_type_id', 'notes', 'code', 'client_id', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    public function ProductType()
    {
        return $this->belongsTo(ProductType::class);
    }
}
