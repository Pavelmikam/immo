<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyView;
use App\Models\RentalRequest;
use App\Models\User;
use Carbon\Carbon;

class StatisticsService
{
    public function getPropertyStats(Property $property, string $period = '30days'): array
    {
        $start = $this->getPeriodStart($period);

        $totalViews  = $property->propertyViews()
                                ->where('viewed_at', '>=', $start)
                                ->count();

        $uniqueViews = $property->propertyViews()
                                ->where('viewed_at', '>=', $start)
                                ->whereNotNull('user_id')
                                ->distinct('user_id')
                                ->count('user_id');

        $requests      = RentalRequest::forProperty($property->id)
                                       ->where('created_at', '>=', $start);
        $totalRequests = (clone $requests)->count();
        $accepted      = (clone $requests)->where('status', 'acceptee')->count();
        $refused       = (clone $requests)->where('status', 'refusee')->count();
        $pending       = (clone $requests)->where('status', 'en_attente')->count();

        $conversionRate = $totalViews > 0
            ? round(($totalRequests / $totalViews) * 100, 2)
            : 0.0;

        $viewsByDay = $property->propertyViews()
                               ->where('viewed_at', '>=', $start)
                               ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
                               ->groupBy('date')
                               ->orderBy('date')
                               ->get()
                               ->mapWithKeys(fn ($v) => [$v->date => (int) $v->count]);

        return [
            'property_id'     => $property->id,
            'period'          => $period,
            'views'           => [
                'total'   => $totalViews,
                'unique'  => $uniqueViews,
                'by_day'  => $viewsByDay,
            ],
            'requests'        => [
                'total'      => $totalRequests,
                'en_attente' => $pending,
                'acceptees'  => $accepted,
                'refusees'   => $refused,
            ],
            'conversion_rate' => $conversionRate,
            'favorites_count' => $property->favorites_count,
        ];
    }

    public function getOwnerDashboard(User $owner, string $period = '30days'): array
    {
        $start       = $this->getPeriodStart($period);
        $propertyIds = Property::byOwner($owner->id)->pluck('id')->toArray();
        $properties  = Property::byOwner($owner->id)->get();

        $totalViews = empty($propertyIds) ? 0 :
            PropertyView::whereIn('property_id', $propertyIds)
                        ->where('viewed_at', '>=', $start)
                        ->count();

        $totalRequests = empty($propertyIds) ? 0 :
            RentalRequest::whereIn('property_id', $propertyIds)
                          ->where('created_at', '>=', $start)
                          ->count();

        $acceptedRequests = empty($propertyIds) ? 0 :
            RentalRequest::whereIn('property_id', $propertyIds)
                          ->where('status', 'acceptee')
                          ->where('created_at', '>=', $start)
                          ->count();

        $potentialRevenue = Property::byOwner($owner->id)
                                    ->where('status', 'active')
                                    ->sum('price');

        return [
            'owner_id'          => $owner->id,
            'period'            => $period,
            'properties'        => [
                'total'   => $properties->count(),
                'active'  => $properties->where('status', 'active')->count(),
                'pending' => $properties->where('status', 'pending')->count(),
                'draft'   => $properties->where('status', 'draft')->count(),
            ],
            'views_total'       => $totalViews,
            'requests_total'    => $totalRequests,
            'requests_accepted' => $acceptedRequests,
            'potential_revenue' => (int) $potentialRevenue,
            'top_properties'    => $properties->sortByDesc('views_count')
                                              ->take(5)
                                              ->values()
                                              ->map(fn (Property $p) => [
                                                  'id'          => $p->id,
                                                  'title'       => $p->title,
                                                  'views_count' => $p->views_count,
                                                  'status'      => $p->status,
                                              ]),
        ];
    }

    public function getTenantDashboard(User $tenant, string $period = '30days'): array
    {
        $start    = $this->getPeriodStart($period);
        $requests = RentalRequest::forTenant($tenant->id)
                                  ->where('created_at', '>=', $start);

        return [
            'tenant_id'          => $tenant->id,
            'period'             => $period,
            'requests'           => [
                'total'      => (clone $requests)->count(),
                'en_attente' => (clone $requests)->where('status', 'en_attente')->count(),
                'acceptees'  => (clone $requests)->where('status', 'acceptee')->count(),
                'refusees'   => (clone $requests)->where('status', 'refusee')->count(),
                'annulees'   => (clone $requests)->where('status', 'annulee')->count(),
            ],
            'favorites_count'    => $tenant->favorites()->count(),
            'saved_searches'     => $tenant->savedSearches()->count(),
            'contributor_points' => $tenant->contributor_points,
            'badges'             => $tenant->contributorBadges()->pluck('badge'),
        ];
    }

    public function getAdminAdvancedStats(string $period = '30days', ?string $city = null): array
    {
        $start = $this->getPeriodStart($period);

        $newUsersByDay = User::where('created_at', '>=', $start)
                             ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                             ->groupBy('date')->orderBy('date')
                             ->pluck('count', 'date');

        $newPropertiesByDay = Property::where('created_at', '>=', $start)
                                      ->when($city, fn ($q) => $q->byCity($city))
                                      ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                                      ->groupBy('date')->orderBy('date')
                                      ->pluck('count', 'date');

        $topCities = Property::where('status', 'active')
                              ->selectRaw('city, COUNT(*) as count')
                              ->groupBy('city')->orderByDesc('count')->limit(10)
                              ->get()->map(fn ($r) => ['city' => $r->city, 'count' => (int) $r->count]);

        $topTypes = Property::where('status', 'active')
                             ->selectRaw('type, COUNT(*) as count, AVG(price) as avg_price')
                             ->groupBy('type')->orderByDesc('count')
                             ->get()->map(fn ($r) => [
                                 'type'      => $r->type,
                                 'count'     => (int) $r->count,
                                 'avg_price' => (int) round($r->avg_price, 0),
                             ]);

        $totalDecided  = RentalRequest::where('created_at', '>=', $start)
                                       ->whereIn('status', ['acceptee', 'refusee'])->count();
        $totalAccepted = RentalRequest::where('created_at', '>=', $start)
                                       ->where('status', 'acceptee')->count();
        $acceptanceRate = $totalDecided > 0
            ? round(($totalAccepted / $totalDecided) * 100, 2)
            : 0.0;

        $avgPriceByCity = Property::where('status', 'active')
                                   ->selectRaw('city, AVG(price) as avg_price, COUNT(*) as count')
                                   ->groupBy('city')->orderByDesc('count')->limit(10)
                                   ->get()->map(fn ($r) => [
                                       'city'      => $r->city,
                                       'avg_price' => (int) round($r->avg_price, 0),
                                       'count'     => (int) $r->count,
                                   ]);

        return [
            'period'                => $period,
            'new_users_by_day'      => $newUsersByDay,
            'new_properties_by_day' => $newPropertiesByDay,
            'top_cities'            => $topCities,
            'top_types'             => $topTypes,
            'acceptance_rate'       => $acceptanceRate,
            'avg_price_by_city'     => $avgPriceByCity,
        ];
    }

    public function getPeriodStart(string $period): Carbon
    {
        return match ($period) {
            '7days'  => now()->subDays(7),
            '90days' => now()->subDays(90),
            '1year'  => now()->subYear(),
            default  => now()->subDays(30),
        };
    }
}
