<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FeedPart extends Model
{
    protected $table = 'marketplace_feed_parts';

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
        'id',
        'feed_id',
        'part_number',
        'file_name',
        'path',
        'created_at',
        'updated_at'
    ];

    public function feed()
    {
        return $this->belongsTo(Feed::class, 'feed_id');
    }
}
