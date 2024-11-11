<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPhone extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'phone_no', 'is_primary'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
