<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ListingGenerationTypeTemplate extends Model
{
    // Set a custom table name
    protected $table = 'listing_generation_type_template';

    protected $keyType = 'string';

    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = $model->code = (string) Str::uuid();
            }
        });
    }

    protected $fillable = ['code','template','default_template','generation_type_id','used_by_store','status','created_by','updated_by','created_at','updated_at'];

    public function listingType()
    {
        return $this->belongsTo(ListingGenerationType::class,'generation_type_id');
    }
}
