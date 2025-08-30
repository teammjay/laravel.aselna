<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UPCCodes extends Model
{
    protected $table = 'upc_codes';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'id',
        'store',
        'upc_code',
        'company_prefix',
        'product_number',
        'is_used',
        'sequence_id',
    ];

    public function storeData()
    {
        return $this->belongsTo(Store::class, 'store', 'id');
    }
}
