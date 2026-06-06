<?php

namespace App\Services;

use App\Contracts\NeighborhoodScoreServiceInterface;
use App\Models\ContributorBadge;
use App\Models\NeighborhoodReport;
use App\Models\NeighborhoodScore;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NeighborhoodScoreService implements NeighborhoodScoreServiceInterface
{
    private const CRITERIA = [
        'eau', 'electricite', 'securite', 'transport',
        'commerces', 'routes', 'sante', 'education',
    ];

    public function submitReport(User $user, array $data): NeighborhoodReport
    {
        if (!$this->canSubmitReport(
            $user,
            $data['criterion'],
            (float) $data['latitude'],
            (float) $data['longitude']
        )) {
            throw new \DomainException(
                'Vous avez déjà évalué ce critère dans cette zone ce mois-ci.'
            );
        }

        return DB::transaction(function () use ($user, $data) {
            $report = NeighborhoodReport::create([
                ...$data,
                'user_id'      => $user->id,
                'is_validated' => true,
                'is_flagged'   => false,
            ]);

            $user->addContributorPoints(5);

            $this->computeScore(
                $data['city'] ?? '',
                $data['neighborhood'] ?? null
            );

            $this->checkAndAwardBadges($user);

            return $report;
        });
    }

    public function canSubmitReport(
        User $user,
        string $criterion,
        float $latitude,
        float $longitude
    ): bool {
        $exists = NeighborhoodReport::where('user_id', $user->id)
                                    ->where('criterion', $criterion)
                                    ->where('created_at', '>=', now()->subDays(30))
                                    ->nearLocation($latitude, $longitude, 2.0)
                                    ->exists();

        return !$exists;
    }

    public function computeScore(string $city, ?string $neighborhood = null): Collection
    {
        $periodStart = now()->subMonths(3)->toDateString();
        $periodEnd   = now()->toDateString();
        $scores      = collect();

        foreach (self::CRITERIA as $criterion) {
            $reports = NeighborhoodReport::validated()
                                         ->notFlagged()
                                         ->byCriterion($criterion)
                                         ->byCity($city)
                                         ->when($neighborhood,
                                             fn ($q) => $q->where('neighborhood', $neighborhood)
                                         )
                                         ->recent(3)
                                         ->get();

            if ($reports->isEmpty()) {
                continue;
            }

            $averageScore    = round($reports->avg('score'), 2);
            $uniqueReporters = $reports->unique('user_id')->count();
            $centerLat       = $reports->avg('latitude');
            $centerLng       = $reports->avg('longitude');

            $score = NeighborhoodScore::updateOrCreate(
                [
                    'city'         => $city,
                    'neighborhood' => $neighborhood,
                    'criterion'    => $criterion,
                ],
                [
                    'center_latitude'  => $centerLat,
                    'center_longitude' => $centerLng,
                    'average_score'    => $averageScore,
                    'report_count'     => $reports->count(),
                    'unique_reporters' => $uniqueReporters,
                    'period_start'     => $periodStart,
                    'period_end'       => $periodEnd,
                    'computed_at'      => now(),
                ]
            );

            $scores->push($score);
        }

        if ($scores->isNotEmpty()) {
            $globalScore = round($scores->avg('average_score'), 2);
            NeighborhoodScore::where('city', $city)
                              ->when($neighborhood,
                                  fn ($q) => $q->where('neighborhood', $neighborhood)
                              )
                              ->update(['global_score' => $globalScore]);
        }

        return $scores;
    }

    public function getScoreForLocation(
        float $latitude,
        float $longitude,
        float $radiusKm = 2.0
    ): ?array {
        $reports = NeighborhoodReport::validated()
                                     ->notFlagged()
                                     ->recent(3)
                                     ->nearLocation($latitude, $longitude, $radiusKm)
                                     ->get();

        if ($reports->isEmpty()) {
            return null;
        }

        $byCriterion = $reports->groupBy('criterion');

        $criteriaScores = $byCriterion->map(fn ($group, $criterion) => [
            'criterion'    => $criterion,
            'score'        => round($group->avg('score'), 2),
            'report_count' => $group->count(),
        ]);

        $globalScore = round($criteriaScores->avg('score'), 2);

        return [
            'latitude'     => $latitude,
            'longitude'    => $longitude,
            'radius_km'    => $radiusKm,
            'global_score' => $globalScore,
            'criteria'     => $criteriaScores->values()->toArray(),
            'report_count' => $reports->count(),
        ];
    }

    public function getScoreHistory(
        string $city,
        ?string $neighborhood,
        string $criterion
    ): array {
        $history = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $avg   = NeighborhoodReport::validated()
                                       ->notFlagged()
                                       ->byCriterion($criterion)
                                       ->byCity($city)
                                       ->when($neighborhood,
                                           fn ($q) => $q->where('neighborhood', $neighborhood)
                                       )
                                       ->whereYear('created_at', $month->year)
                                       ->whereMonth('created_at', $month->month)
                                       ->avg('score');

            $history[] = [
                'month'         => $month->format('Y-m'),
                'average_score' => $avg ? round((float) $avg, 2) : null,
                'label'         => $month->format('M Y'),
            ];
        }

        return $history;
    }

    public function checkAndAwardBadges(User $user): array
    {
        $awarded     = [];
        $reportCount = $user->neighborhoodReports()
                            ->where('is_validated', true)
                            ->count();

        $badges = [
            'premier_signalement' => $reportCount >= 1,
            'contributeur_actif'  => $reportCount >= 10,
            'expert_quartier'     => $reportCount >= 50,
            'explorateur'         => $user->neighborhoodReports()
                                          ->where('is_validated', true)
                                          ->distinct('neighborhood')
                                          ->count('neighborhood') >= 3,
            'fiable'              => $user->neighborhoodReports()
                                          ->where('is_flagged', true)
                                          ->count() === 0
                                    && $reportCount >= 1,
        ];

        foreach ($badges as $badge => $earned) {
            if ($earned && !$user->hasBadge($badge)) {
                ContributorBadge::create([
                    'user_id'    => $user->id,
                    'badge'      => $badge,
                    'awarded_at' => now(),
                ]);
                $awarded[] = $badge;
            }
        }

        return $awarded;
    }
}
