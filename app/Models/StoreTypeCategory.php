<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreTypeCategory extends Model
{
    // Set a custom table name
    protected $table = 'store_type_category';

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


    protected $fillable = ['id','code','name','store_type_id','parent_id','category_id','store_type_code','created_by','updated_by','created_at','updated_at'];

    public function parent()
    {
        return $this->belongsTo(StoreTypeCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasOne(StoreTypeCategory::class, 'parent_id');
    }

    public function storetype()
    {
        return $this->belongsTo(StoreType::class,'store_type_id');
    }

    public function StoreTypes()
    {
        return $this->belongsTo(StoreType::class,'store_type_id');
    }
}
