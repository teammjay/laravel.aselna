<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreLogisticCenter extends Model
{
    // Set a custom table name
    protected $table = 'store_logistic_center';

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


    protected $fillable = ['id', 'store_id', 'logistic_center_id', 'overwrite_from_address', 'overwrite_return_address', 'created_by', 'updated_by'];

    public function LogisticCenter()
    {
        return $this->belongsTo(LogisticCenter::class);
    }
}
