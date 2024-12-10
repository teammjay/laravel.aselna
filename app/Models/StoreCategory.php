<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreCategory extends Model
{
    protected $table = 'store_category';

    public $timestamps = true;

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

    protected $fillable = ['store_id','code','name','url','text_generation_template_id'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

}
