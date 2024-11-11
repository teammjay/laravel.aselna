<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'email_address', 'is_primary'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
