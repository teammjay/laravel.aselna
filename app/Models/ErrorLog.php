<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ErrorLog extends Model
{
    //
    protected $table = 'error_logs';

    // timestamps
    public $timestamps = true;

    // add id as uuid
    protected $primaryKey = 'id';
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string)Str::uuid();
            }
        });
    }

    protected $fillable = ['id', 'store_id', 'error_level', 'error_source', 'operation', 'status','message', 'stack_trace','resolved_date','hash_string','error_count','created_at','updated_at'];
}
