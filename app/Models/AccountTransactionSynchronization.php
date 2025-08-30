<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AccountTransactionSynchronization extends Model
{
    // Set a custom table name
    protected $table = 'account_transaction_synchronization';

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


    protected $fillable = ['id','shipping_account_id','filter_start_date','filter_end_date','created_at','updated_at'];
}
