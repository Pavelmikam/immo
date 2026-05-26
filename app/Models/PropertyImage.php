<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class PropertyImage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id', 'original_path', 'optimized_path', 'thumbnail_path',
        'order', 'is_primary', 'caption',
    ];

    protected $casts = [
        'order'      => 'integer',
        'is_primary' => 'boolean',
    ];

    protected $appends = ['original_url', 'optimized_url', 'thumbnail_url'];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function getOriginalUrlAttribute(): string
    {
        return Storage::disk(config('app.media_disk', 'media'))->url($this->original_path);
    }

    public function getOptimizedUrlAttribute(): string
    {
        return Storage::disk(config('app.media_disk', 'media'))->url($this->optimized_path);
    }

    public function getThumbnailUrlAttribute(): string
    {
        return Storage::disk(config('app.media_disk', 'media'))->url($this->thumbnail_path);
    }
}
