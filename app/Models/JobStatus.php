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
        'parent_job_id',
        'order',
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

    /**
     * Get the parent job status.
     */
    public function parent()
    {
        return $this->belongsTo(JobStatus::class, 'parent_job_id', 'job_id');
    }

    /**
     * Get the child job statuses.
     */
    public function children()
    {
        return $this->hasMany(JobStatus::class, 'parent_job_id', 'job_id')->orderBy('order');
    }

    /**
     * Scope to get only parent jobs (jobs without a parent).
     */
    public function scopeParents($query)
    {
        return $query->whereNull('parent_job_id');
    }

    /**
     * Check if this job is a parent job.
     */
    public function isParent(): bool
    {
        return $this->parent_job_id === null;
    }
}
