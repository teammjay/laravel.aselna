<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';
    protected $keyType = 'string';

    protected $fillable = [
        'id','first_name', 'last_name', 'full_name', 'store_id', 'user_id', 'group'
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

    public function emails()
    {
        return $this->hasMany(CustomerEmail::class);
    }

    public function phones()
    {
        return $this->hasMany(CustomerPhone::class);
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class);
    }
}
