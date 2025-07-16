<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductInterface extends Model
{
    // Set a custom table name
    protected $table = 'product_interface';

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


    protected $fillable = ['product_id','interface_id'];

    public function product()
    {
        return $this->belongsTo(Catalog::class,'product_id','product_id')->where(function ($q) {
            $q->where('product_status', 'active');
        });
    }

    public function interfaces()
    {
        return $this->belongsTo(Interfaces::class, 'interface_id','id');
    }
}
