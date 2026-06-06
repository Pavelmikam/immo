<?php

namespace App\Exports;

use App\Models\RentalRequest;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RentalRequestsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        private ?string $status = null,
        private ?int $propertyId = null,
        private ?int $tenantId = null
    ) {}

    public function query(): Builder
    {
        return RentalRequest::with(['property', 'tenant'])
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->propertyId, fn ($q) => $q->forProperty($this->propertyId))
            ->when($this->tenantId, fn ($q) => $q->forTenant($this->tenantId))
            ->latest();
    }

    public function headings(): array
    {
        return [
            'ID', 'Bien', 'Ville', 'Loyer (FCFA)', 'Locataire',
            'Email locataire', 'Statut', 'Message (extrait)',
            'Réponse propriétaire', 'Date demande', 'Date décision',
        ];
    }

    public function map($request): array
    {
        return [
            $request->id,
            $request->property?->title,
            $request->property?->city,
            number_format($request->property?->price ?? 0, 0, ',', ' '),
            $request->tenant?->name,
            $request->tenant?->email,
            $request->status,
            mb_substr($request->message ?? '', 0, 100),
            mb_substr($request->owner_response ?? '', 0, 100),
            $request->created_at?->format('d/m/Y H:i'),
            $request->decided_at?->format('d/m/Y H:i'),
        ];
    }
}
