<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'id','first_name', 'last_name', 'full_name', 'store_id', 'user_id', 'group'
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
