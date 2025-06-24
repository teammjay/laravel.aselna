<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomerEmail extends Model
{
    use HasFactory;

    protected $table = 'customer_emails';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string)Str::uuid();
            }
        });
    }

    protected $fillable = [
        'customer_id', 'email_address', 'is_primary'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
