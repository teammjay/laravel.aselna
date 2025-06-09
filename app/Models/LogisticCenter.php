<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LogisticCenter extends Model
{
    // Set a custom table name
    protected $table = 'logistic_center';

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


    protected $fillable = ['id', 'name', 'client_id', 'center_type', 'from_address_id', 'return_address_id', 'created_by', 'updated_by'];

    public function StoreLogisticCenter()
    {
        return $this->hasOne(StoreLogisticCenter::class);
    }

    public function fromAddress()
    {
        return $this->belongsTo(PostalAddress::class, 'from_address_id');
    }

    public function returnAddress()
    {
        return $this->belongsTo(PostalAddress::class, 'return_address_id');
    }

    public function documents()
    {
        return $this->hasMany(ShipmentDocumentSetting::class, 'shipping_from_location_id');
    }
}
