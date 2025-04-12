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
        'last_run',
        'description',
        'class_name',
    ];
}
