<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HdMessagePart extends Model
{
    // Set a custom table name
    protected $table = 'hd_message_part';

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

    protected $fillable = ['id','client_id','mime_type','content','charset','message_id','created_by','updated_by','created_at','updated_at'];
}
