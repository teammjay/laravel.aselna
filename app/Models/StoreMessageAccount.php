<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreMessageAccount extends Model
{
    // Set a custom table name
    protected $table = 'store_message_account';

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


    protected $fillable = ['store_id','name','email','client_id','incoming_account_id','outgoing_account_id','api_account','active','version','created_by','updated_by'];

}
