<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AdminLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id', 'action', 'loggable_type', 'loggable_id',
        'before', 'after', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'before' => 'array',
        'after'  => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeByAdmin(Builder $query, int $adminId): Builder
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', 'LIKE', "{$action}%");
    }
}
