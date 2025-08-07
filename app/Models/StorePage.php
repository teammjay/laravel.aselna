<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StorePage extends Model
{
    // Set a custom table name
    protected $table = 'store_page';

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

    protected $fillable = ['id','store_id','page_type','title','text','created_by','updated_by','created_at','updated_at','page_type_id'];

    public function PageType()
    {
        return $this->belongsTo(PageType::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class,'store_id');
    }

    public static function getStorePageByPageType($store_id, $page_type){

        $page = self::with('PageType')->where('store_id', $store_id)->where('page_type',$page_type)->first();
        $content = explode(PHP_EOL,$page->text);
        return $content;
    }
}
