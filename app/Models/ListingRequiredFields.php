<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingRequiredFields extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_field',
        'parent_value',
        'dependent_field',
        'is_required',
        'category',
        'store_type_id',
        'is_active',
        'description'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the store type that owns the field dependency.
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
        return $query->where(function ($q) use ($category, $storeTypeId) {
            $q->where('category', $category)
              ->orWhereNull('category');
        })->where(function ($q) use ($storeTypeId) {
            $q->where('store_type_id', $storeTypeId)
              ->orWhereNull('store_type_id');
        });
    }

    /**
     * Get dependencies for a specific parent field and value
     */
    public static function getDependenciesFor($parentField, $parentValue, $category = null, $storeTypeId = null)
    {
        return self::active()
            ->where('parent_field', $parentField)
            ->where('parent_value', $parentValue)
            ->forCategoryAndStore($category, $storeTypeId)
            ->get();
    }

    /**
     * Check if a field has dependencies
     */
    public static function hasDependencies($field, $category = null, $storeTypeId = null)
    {
        return self::active()
            ->where('parent_field', $field)
            ->forCategoryAndStore($category, $storeTypeId)
            ->exists();
    }
}
