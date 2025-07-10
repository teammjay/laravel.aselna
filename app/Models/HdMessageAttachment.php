<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HdMessageAttachment extends Model
{
    // Set a custom table name
    protected $table = 'hd_message_attachment';

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

    protected $fillable = ['id','client_id','message_id','attachment_id','file_id','deleted_by','deleted_at','created_by','updated_by','created_at','updated_at'];
}
