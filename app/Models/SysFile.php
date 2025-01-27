<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SysFile extends Model
{
    protected $table = 'sys_file';

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

    protected $fillable = ['id','name','ext','file_size','location','created_by','updated_by','deleted_by','deleted_at','created_at','updated_at'];
}
