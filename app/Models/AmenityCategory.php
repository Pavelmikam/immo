<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AmenityCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category', 'value', 'label', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopePropertyTypes(Builder $query): Builder
    {
        return $this->scopeByCategory($query, 'property_type')->active();
    }

    public function scopeAmenities(Builder $query): Builder
    {
        return $this->scopeByCategory($query, 'amenity')->active();
    }

    public function scopeCharges(Builder $query): Builder
    {
        return $this->scopeByCategory($query, 'charge')->active();
    }
}
