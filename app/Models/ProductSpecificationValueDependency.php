<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSpecificationValueDependency extends Model
{
    use HasFactory;

    public $table = 'product_specification_value_dependencies';

    protected $fillable = [
        'parent_field_name',
        'parent_field_value_id',
        'child_field_name',
        'child_field_value_id',
        'category',
        'store_type_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the store type that owns the field value dependency.
     */
    public function storeType(): BelongsTo
    {
        return $this->belongsTo(StoreType::class);
    }

    /**
     * Scope to get active dependencies
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get dependencies for specific category and store type
     */
    public function scopeForCategoryAndStore($query, $category = null, $storeTypeId = null)
    {
        return $query->where('category', $category)
                    ->where('store_type_id', $storeTypeId);
    }

    /**
     * Get child values for a given parent field and value
     */
    public static function getChildValues($parentFieldName, $parentValueId, $childFieldName, $category, $storeTypeId)
    {
        return self::active()
            ->where('parent_field_name', $parentFieldName)
            ->where('parent_field_value_id', $parentValueId)
            ->where('child_field_name', $childFieldName)
            ->where('category', $category)
            ->where('store_type_id', $storeTypeId)
            ->pluck('child_field_value_id')
            ->toArray();
    }
}
