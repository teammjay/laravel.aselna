<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketPlaceCategoryMappings extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'marketplace_category_mappings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'store_id',
        'name',
        'category_id',
        'marketplace_category_id',
        'marketplace_parent_id',
    ];

    /**
     * Get the store that owns this category mapping.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
