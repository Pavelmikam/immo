<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'criteria', 'notifications_enabled', 'last_notified_at',
    ];

    protected $casts = [
        'criteria'              => 'array',
        'notifications_enabled' => 'boolean',
        'last_notified_at'      => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
