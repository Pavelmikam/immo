<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NeighborhoodScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'city'             => $this->city,
            'neighborhood'     => $this->neighborhood,
            'criterion'        => $this->criterion,
            'average_score'    => (float) $this->average_score,
            'global_score'     => $this->global_score ? (float) $this->global_score : null,
            'score_label'      => $this->resource->getScoreLabel(),
            'score_color'      => $this->resource->getScoreColor(),
            'report_count'     => $this->report_count,
            'unique_reporters' => $this->unique_reporters,
            'period_start'     => $this->period_start?->toDateString(),
            'period_end'       => $this->period_end?->toDateString(),
            'computed_at'      => $this->computed_at?->toIso8601String(),
        ];
    }
}
