<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ScheduledJobs extends Model
{
    // Set a custom table name
    protected $table = 'scheduled_cron_jobs';

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


    protected $fillable = ['id','name','class_name','cron_expression','parameters','last_run_at','last_status','is_active','created_at','updated_at'];

}
