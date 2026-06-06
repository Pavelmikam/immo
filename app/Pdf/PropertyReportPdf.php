<?php

namespace App\Pdf;

use App\Models\Property;
use Barryvdh\DomPDF\Facade\Pdf;

class PropertyReportPdf
{
    public function __construct(
        private Property $property,
        private array $stats
    ) {}

    public function generate(): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView(
            'pdf.property-report',
            [
                'property'    => $this->property->load(['owner']),
                'stats'       => $this->stats,
                'generatedAt' => now()->format('d/m/Y H:i'),
            ]
        )->setPaper('A4', 'portrait');
    }
}
