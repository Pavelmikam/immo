<?php

namespace App\Contracts;

use App\Models\Property;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface PropertyServiceInterface
{
    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function create(User $owner, array $data): Property;
    public function update(Property $property, array $data): Property;
    public function submit(Property $property): Property;
    public function archive(Property $property): Property;
    public function delete(Property $property): void;
}
