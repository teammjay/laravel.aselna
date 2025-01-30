<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'messaging_account';

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


    protected $fillable = ['protocol','email','user_id','password','server','port','requires_ssl','requires_tls','requires_authentication','ssl_port','tls_port','filter_from_date','folders','last_sync_date','created_by','updated_by'];
}
