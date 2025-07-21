<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HDMessage extends Model
{
    // Set a custom table name
    protected $table = 'hd_message';

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

    protected $fillable = ['id','client_id','ticket_id','subject','message_date','to_','cc','bcc','message_id','messaging_account_id','message_hash_id','from_email','from_name','message','previous_message','in_reply_to','draft','to_name','links','from_message_user_id','to_message_user_id','author','order_operation_action','parsed_message','reply_to_name','reply_to_email','created_by','updated_by','deleted_by','deleted_at','created_at','updated_at'];

    public function attachments(){
        return $this->hasMany(HdMessageAttachment::class, 'id','message_id');
    }
}
