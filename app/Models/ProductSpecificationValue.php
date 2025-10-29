<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductSpecificationValue extends Model
{
    protected $table = 'product_specification_values';

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

    protected $fillable = ['id', 'product_specification_id', 'field_value', 'store_type_id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'category'];

    public function specificationInfo()
    {
        return $this->belongsTo(ProductSpecifcationUniqueInfo::class, 'product_specification_id');
    }
}
