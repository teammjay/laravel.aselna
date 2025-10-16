<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JobStatus extends Model
{
    use HasFactory;

    protected $table = 'job_statuses';

    protected $fillable = [
        'id',
        'job_id',
        'type',
        'queue',
        'status',
        'output',
        'chain_id',
        'started_at',
        'finished_at',
        'tags',
        'updated_at',
        'created_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
