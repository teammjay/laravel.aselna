<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleParentJob extends Model
{
    protected $table = 'schedule_jobs_parent';

    protected $fillable = [
        'parent_id',
        'job_id',
    ];

    public $timestamps = false;

    public $incrementing = false;

}
