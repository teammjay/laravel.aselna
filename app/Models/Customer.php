<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name', 'last_name', 'full_name', 'store_id', 'user_id', 'group', 'marketplace_customer_id', 'email'
    ];

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
