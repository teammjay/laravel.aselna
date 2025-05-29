<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PostalAddress extends Model
{
    protected $table = 'postal_addresses';

    protected $fillable = [
        'id',
        'first_name',
        'last_name',
        'company',
        'address_line_1',
        'address_line_2',
        'address_line_3',
        'city',
        'state_or_region',
        'postal_code',
        'country',
        'country_code',
        'phone',
        'email',
        'district',
        'client'
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }
}
