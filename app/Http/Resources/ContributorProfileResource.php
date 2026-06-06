<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContributorProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'contributor_points' => $this->contributor_points,
            'reports_count'      => $this->neighborhoodReports()->count(),
            'badges'             => $this->contributorBadges->pluck('badge'),
            'latest_reports'     => NeighborhoodReportResource::collection(
                $this->neighborhoodReports()->latest()->take(3)->get()
            ),
        ];
    }
}
