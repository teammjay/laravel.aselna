<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductSpecifcationUniqueInfo extends Model
{
    protected $table = 'product_specification_unique_info';

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

    protected $fillable = ['id', 'field_name', 'created_by', 'updated_by', 'created_at', 'updated_at'];

    public function values()
    {
        return $this->hasMany(ProductSpecificationValue::class, 'product_specification_id', 'id');
    }
}
