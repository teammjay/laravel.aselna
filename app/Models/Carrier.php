<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Carrier extends Model
{
    // Set a custom table name
    protected $table = 'carrier';

    public $timestamps = true;

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

    protected $fillable = ['name','code','image','thumbnail_image','sort_order','client','created_by','updated_by','created_at','updated_at'];

    // wrap image and thumbnail_image with full URL from the image table currently image holds the reference_id of the image table
    public function getImage()
    {
        $image = Images::where('reference_id', $this->image)->first();
        if ($image) {
            return url('storage/' . $image->file_path);
        }
        return null;
    }

    public function getThumbnailImage()
    {
        $thumbnailImage = Images::where('reference_id', $this->thumbnail_image)->first();
        if ($thumbnailImage) {
            return url('storage/' . $thumbnailImage->file_path);
        }
        return null;
    }
}
