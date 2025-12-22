<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StoreScheduledCronJobs extends Model
{
    // Set a custom table name
    protected $table = 'store_scheduled_cron_jobs';

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


    protected $fillable = ['id','store_id','store_type_id','scheduled_job_id','is_active','created_at','updated_at'];


}
