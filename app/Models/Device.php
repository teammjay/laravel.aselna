<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{

    protected $table = 'device';

    protected $fillable = [
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

    protected $casts = [
        'release_date' => 'date:Y-m-d',
    ];

    public function deviceSubType()
    {
        return $this->belongsTo(DeviceTypes::class);
    }

    public function deviceSeries()
    {
        return $this->belongsTo(DeviceSeries::class);
    }

    public function rating()
    {
        return $this->belongsTo(DeviceRating::class);
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

    public function setReleaseDateAttribute($value)
    {
        if (!isset($value)) return;
        $this->attributes['release_date'] = Carbon::createFromFormat('m/d/Y', $value)->format('Y-m-d');
    }

    public function getReleaseDateAttribute($value)
    {
        if (!isset($value)) return '';
        return Carbon::parse($value)->format('m/d/Y');
    }

}
