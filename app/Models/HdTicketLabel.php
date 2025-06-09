<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HdTicketLabel extends Model
{
    // Set a custom table name
    protected $table = 'hd_ticket_label';

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

    protected $fillable = ['id','client_id','name','parent_id','created_by','updated_by','created_at','updated_at'];

    public function parent(){
        return $this->belongsTo(self::class, 'parent_id');
    }
}
