<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserAccount extends Model
{
    // Set a custom table name
    protected $table = 'user_account';

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


    protected $fillable = ['name','email','user_id','outgoing_account_id','api_account','active','version','created_by','updated_by'];


    public function outgoingAccount(){
        return $this->belongsTo(UserNotificationAccount::class,'outgoing_account_id');
    }
}
