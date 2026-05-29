<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RentalDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_request_id', 'uploaded_by', 'type',
        'file_path', 'original_name', 'mime_type', 'file_size',
        'description', 'is_verified', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'file_size'   => 'integer',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function rentalRequest(): BelongsTo
    {
        return $this->belongsTo(RentalRequest::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Génère un lien de téléchargement signé (5 min par défaut).
     * Les documents ne sont jamais exposés publiquement.
     */
    public function getTemporaryUrl(int $minutes = 5): string
    {
        $disk = config('filesystems.documents_disk', 'documents');

        if (Storage::disk($disk)->providesTemporaryUrls()) {
            return Storage::disk($disk)->temporaryUrl(
                $this->file_path,
                now()->addMinutes($minutes)
            );
        }

        return route('api.documents.download', ['document' => $this->id])
            . '?expires=' . now()->addMinutes($minutes)->timestamp
            . '&signature=' . hash_hmac('sha256', $this->id . $this->file_path, config('app.key'));
    }
}
