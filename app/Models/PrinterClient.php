<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PrinterClient extends Model
{
    // Set a custom table name
    protected $table = 'printer_client';

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


    protected $fillable = ['id','version','client_id','name','machine_key','active','connection_type','dazzle_data_id','deleted_at','deleted_by','updated_by','updated_at','created_by','created_at'];
}
