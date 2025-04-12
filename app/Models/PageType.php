<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PageType extends Model
{
    // Set a custom table name
    protected $table = 'page_types';

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


    protected $fillable = ['type'];

    public function StorePage()
    {
        return $this->hasOne(StorePage::class);
    }

}
