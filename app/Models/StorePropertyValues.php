<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StorePropertyValues extends Model
{
    // Set a custom table name
    protected $table = 'store_property_values';

    public $timestamps = true;

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

    protected $fillable = ['id','store_id','store_property_info_id','value','notes','created_at','updated_at'];

    public function propertyInfo()
    {
        return $this->belongsTo(StorePropertyInfo::class,'store_property_info_id');
    }
}
