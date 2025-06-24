<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ShipmentDocumentSetting extends Model
{
    protected $table = 'shipping_document_setting';

    protected $primaryKey = 'id';
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


    protected $fillable = [
        'id',
        'client',
        'enabled',
        'document_type',
        'printer_id',
        'shipping_from_location_id',
        'page_size_id',
        'two_per_page',
        'pdf_action',
        'rotate',
        'created_at',
        'updated_at',
    ];

    public function printer()
    {
        return $this->belongsTo(Printer::class, 'printer_id');
    }

    public function logisticCenter()
    {
        return $this->belongsTo(LogisticCenter::class, 'shipping_from_location_id');
    }

    public function getLabelFormatAttribute($value)
    {
        return $value ?? ShippingLabelFormat::PDF;
    }
}
