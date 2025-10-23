<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AccountTransaction extends Model
{
    // Set a custom table name
    protected $table = 'shipping_account_transaction';

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


    protected $fillable = ['id','shipping_account_id','store_id','tracking_number','transaction_date','transaction_id','amount','address','status','status_event','status_description','canceled','canceled_date','created_by','created_at','updated_at'];

    public function shippingAccount(){
        return $this->belongsTo(ShippingAccount::class,'shipping_account_id');
    }

    public function store(){
        return $this->belongsTo(Store::class,'store_id');
    }
}
