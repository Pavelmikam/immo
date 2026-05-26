<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface PropertyFilterServiceInterface
{
    public function buildQuery(array $filters): Builder;
    public function allowedFilters(): array;
}
