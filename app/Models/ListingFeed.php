<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ListingFeed extends Model
{

    protected $table = 'listing_feeds';

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

    protected $fillable = ['id','file_id','listing_id','number_of_attempts','status','error_text','feed_id','submit_ts','errors','last_status_check_ts','status_check_count','processing_count','success_count','fail_count','warning_count','number_of_items','client','file_reference_id','store_feed_type','store_id','feed_type','response_file_id'];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
