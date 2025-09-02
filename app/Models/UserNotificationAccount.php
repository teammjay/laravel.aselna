<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserNotificationAccount extends Model
{
    // Set a custom table name
    protected $table = 'user_notification_account';

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


    protected $fillable = ['id','protocol','email','user_id','username','password','server','port','requires_ssl','requires_tls','requires_authentication','ssl_port','tls_port','created_by','updated_by'];
}
