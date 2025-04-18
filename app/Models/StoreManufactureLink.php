<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreManufactureLink extends Model
{
    // Set a custom table name
    protected $table = 'store_manufacturer_link';

    protected $keyType = 'string';

    public $incrementing = false;

    /*protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }*/


    protected $fillable = ['store_id','manufacturer_id'];

    public function manufacturer(){

        return $this->belongsTo(Manufacturer::class,'manufacturer_id');
    }
}
