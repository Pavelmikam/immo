<?php

namespace App\Contracts;

use App\Models\NeighborhoodReport;
use App\Models\User;
use Illuminate\Support\Collection;

interface NeighborhoodScoreServiceInterface
{
    public function submitReport(User $user, array $data): NeighborhoodReport;

    public function computeScore(string $city, ?string $neighborhood = null): Collection;

    public function getScoreForLocation(
        float $latitude,
        float $longitude,
        float $radiusKm = 2.0
    ): ?array;

    public function getScoreHistory(
        string $city,
        ?string $neighborhood,
        string $criterion
    ): array;

    public function canSubmitReport(
        User $user,
        string $criterion,
        float $latitude,
        float $longitude
    ): bool;

    public function checkAndAwardBadges(User $user): array;
}
