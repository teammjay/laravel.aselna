<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StorePage extends Model
{
    // Set a custom table name
    protected $table = 'store_page';

    public $timestamps = false;

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

    protected $fillable = ['store_id','page_type_id','title','text','created_by','updated_by','created_at','updated_at'];

    public function PageType()
    {
        return $this->belongsTo(PageType::class);
    }
}
