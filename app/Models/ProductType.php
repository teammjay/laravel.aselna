<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductType extends Model
{
    // Set a custom table name
    protected $table = 'product_type';

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


    protected $fillable = ['code','name','parent_id','singular_name','simple_name','sort_order'];

    // Define the parent relationship (belongsTo)
    public function parent()
    {
        return $this->belongsTo(ProductType::class, 'parent_id');
    }

    // Define the children relationship (hasOne)
    public function children()
    {
        return $this->hasOne(ProductType::class, 'parent_id');
    }

    public function Interfaces()
    {
        return $this->hasOne(Interfaces::class);
    }

}
