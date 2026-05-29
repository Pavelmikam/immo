<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentalRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id', 'tenant_id', 'status',
        'message', 'owner_response', 'decided_at',
        'visit_scheduled_at', 'visit_confirmed', 'dossier_complete',
    ];

    protected $casts = [
        'decided_at'         => 'datetime',
        'visit_scheduled_at' => 'datetime',
        'visit_confirmed'    => 'boolean',
        'dossier_complete'   => 'boolean',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(RentalDocument::class)->latest();
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'en_attente');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['en_attente', 'acceptee']);
    }

    public function scopeForProperty(Builder $query, int $propertyId): Builder
    {
        return $query->where('property_id', $propertyId);
    }

    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->where('tenant_id', $tenantId);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isPending(): bool
    {
        return $this->status === 'en_attente';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'acceptee';
    }

    public function isDecided(): bool
    {
        return in_array($this->status, ['acceptee', 'refusee']);
    }

    public function canBeCancelledBy(User $user): bool
    {
        return $this->tenant_id === $user->id
            && $this->status === 'en_attente';
    }

    public function canBeDecidedBy(User $user): bool
    {
        return $this->property->isOwnedBy($user)
            && $this->status === 'en_attente';
    }
}
