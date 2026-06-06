<?php

namespace App\Exports;

use App\Contracts\PropertyFilterServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PropertiesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private array $filters = []) {}

    public function query(): Builder
    {
        return app(PropertyFilterServiceInterface::class)
                   ->buildQuery($this->filters)
                   ->with(['owner']);
    }

    public function headings(): array
    {
        return [
            'ID', 'Titre', 'Type', 'Ville', 'Quartier',
            'Prix (FCFA)', 'Surface (m²)', 'Chambres',
            'Statut', 'Propriétaire', 'Email propriétaire',
            'Vues', 'Favoris', 'Demandes',
            'Date création', 'Date approbation',
        ];
    }

    public function map($property): array
    {
        return [
            $property->id,
            $property->title,
            $property->type,
            $property->city,
            $property->district,
            number_format($property->price, 0, ',', ' '),
            $property->surface,
            $property->rooms,
            $property->status,
            $property->owner?->name,
            $property->owner?->email,
            $property->views_count,
            $property->favorites_count,
            $property->requests_count,
            $property->created_at?->format('d/m/Y H:i'),
            $property->published_at?->format('d/m/Y H:i'),
        ];
    }
}
