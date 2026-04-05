<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductSpecificationInfo extends Model
{
    protected $table = 'product_specification_info';

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
    protected $fillable = ['id', 'field_name','field_local_name','field_type','field_description','required','store_type_id','created_at','updated_at'];

    public function storeType(){
        return $this->belongsTo(StoreType::class,'store_type_id');
    }

    public function productTypes(){
        return $this->hasMany(productSpecificationInfoCategoryLink::class,'specification_info_id','id');
    }

}
