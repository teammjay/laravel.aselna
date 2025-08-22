<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Feed extends Model
{
    // Set a custom table name
    protected $table = 'marketplace_feeds';

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

    protected $fillable = ['id','store_id','marketplace','file_type','file_name','path','status','created_by','updated_by','deleted_by','deleted_at','created_at','updated_at'];

    public function store(){
        return $this->belongsTo(Store::class,'store_id');
    }
}
