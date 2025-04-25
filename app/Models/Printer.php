<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Printer extends Model
{
    // Set a custom table name
    protected $table = 'printer';

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


    protected $fillable = ['id','client_id','name','label_format','status_message','internal_name','dpi','printer_client_id','status','printer_type','deleted_at','deleted_by','updated_by','updated_at','created_by','created_at'];

    public function printerClient(){
        return $this->belongsTo(PrinterClient::class,'printer_client_id');
    }
}
