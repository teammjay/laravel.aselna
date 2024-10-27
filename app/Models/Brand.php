<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'notes1',
        'notes2',
        'notes3',
        'order_no',
        'created_by',
        'updated_by',
        'image',
        'thumbnail_image',
        'code',
        'status'
    ];

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
