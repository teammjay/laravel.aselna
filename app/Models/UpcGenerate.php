<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UpcGenerate extends Model
{
    protected $table = 'upc_generates';

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
        'store',
        'company_prefix',
        'product_number',
        'number_of_codes',
    ];

    public function storeData()
    {
        return $this->belongsTo(Store::class, 'store', 'id');
    }
}
