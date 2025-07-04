<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceAttributeMapping extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'marketplace_attribute_mappings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'store_id',
        'code',
        'value',
        'code_id',
        'marketplace_code',
    ];

    /**
     * Get the store that owns this attribute mapping.
     */
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
