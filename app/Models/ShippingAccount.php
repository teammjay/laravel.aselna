<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingAccount extends Model
{
    // Set a custom table name
    protected $table = 'shipping_account';

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


    protected $fillable = ['id','name','user_id','password','api_key','client','shipping_solution_id','account_id','pass_phrase','requester_id','dazzle_client_id','sandbox_account','maximum_allowable_balance','auto_recharge','auto_recharge_threshold'];
}
