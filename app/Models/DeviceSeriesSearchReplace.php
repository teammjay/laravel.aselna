<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class DeviceSeriesSearchReplace extends Model
{
    use HasFactory;

    protected $table = 'device_series_search_replace_text';

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

    protected $fillable = ['id','device_series_id', 'replace_search_text', 'replace_value', 'created_at','updated_at'];
}
