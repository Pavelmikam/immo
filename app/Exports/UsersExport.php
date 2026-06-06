<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private ?string $role = null,
        private ?bool $isActive = null
    ) {}

    public function query(): Builder
    {
        return User::query()
            ->when($this->role, fn ($q) => $q->where('role', $this->role))
            ->when($this->isActive !== null,
                fn ($q) => $q->where('is_active', $this->isActive)
            )
            ->withCount(['properties', 'rentalRequests'])
            ->latest();
    }

    public function headings(): array
    {
        return [
            'ID', 'Nom', 'Email', 'Rôle', 'Téléphone', 'Ville',
            'Actif', 'Email vérifié', 'Annonces', 'Demandes',
            'Points contribution', 'Date inscription',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->name,
            $user->email,
            $user->role,
            $user->phone,
            $user->city,
            $user->is_active ? 'Oui' : 'Non',
            $user->email_verified_at ? 'Oui' : 'Non',
            $user->properties_count,
            $user->rental_requests_count,
            $user->contributor_points,
            $user->created_at?->format('d/m/Y'),
        ];
    }
}
