<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Manufacturer extends Model
{
    // Set a custom table name
    protected $table = 'manufacturer';

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


    protected $fillable = ['name','code','notes','image_id','thumbnail_id','client_id','sort_order','version','created_by','updated_by','deleted_by','deleted_at'];

}
