<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShippingAccountPurchase extends Model
{
    // Set a custom table name
    protected $table = 'shipping_account_purhcase';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'shipping_account_id',
        'purchase_date',
        'amount',
        'currency',
        'errors',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function shippingAccount()
    {
        return $this->belongsTo(ShippingAccount::class, 'shipping_account_id');
    }
}
