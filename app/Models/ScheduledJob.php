<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduledJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'last_run',
        'next_run',
        'notes'
    ];

    public function executions(): HasMany
    {
        return $this->hasMany(JobExecution::class);
    }
}