<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FeedConfiguration extends Model
{
    protected $table = 'feed_configurations';

    protected $fillable = [
        'id',
        'name',
        'store_id',
        'feed_type',
        'file_type',
        'filters',
        'created_by'
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'filters' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string)Str::uuid();
            }
        });
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
