<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Device extends Model
{

    protected $table = 'device';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'dimensions_height',
        'dimensions_width',
        'name',
        'release_date',
        'screen_size_unit',
        'image',
        'thumbnail_image',
        'notes',
        'order',
        'code',
        'dimensions_length',
        'dimensions_length_unit',
        'device_sub_type_id',
        'device_series_id',
        'rating_id',
        'screen_id',
        'brand_id',
        'device_type_id',
        'created_by',
        'updated_by',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    protected $casts = [
        'release_date' => 'date:Y-m-d',
    ];

    public function deviceSubType()
    {
        return $this->belongsTo(DeviceSubTypes::class);
    }

    public function deviceSeries()
    {
        return $this->belongsTo(DeviceSeries::class,'device_series_id');
    }

    public function rating()
    {
        return $this->belongsTo(DeviceRating::class, 'rating_id')->withDefault(['name' => '']);
    }

    public function screen()
    {
        return $this->belongsTo(DeviceScreen::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function deviceType()
    {
        return $this->belongsTo(DeviceTypes::class);
    }

    public function interfaces()
    {
        return $this->hasMany(DeviceInterface::class);
    }

    public function setReleaseDateAttribute($value)
    {
        if (!isset($value)) return;
        //check if the value already in the format or not
        if ( Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d') == $value ) {
            $this->attributes['release_date'] = $value;
            return;
        }
        $this->attributes['release_date'] = Carbon::createFromFormat('m/d/Y', $value)->format('Y-m-d');
    }

    public function getReleaseDateAttribute($value)
    {
        if (!isset($value)) return '';
        return Carbon::parse($value)->format('m/d/Y');
    }

    public function carriers()
    {
        // map to table device_carrier
        return $this->belongsToMany(Carrier::class, 'device_carrier', 'device_id', 'carrier_id');
    }

}
