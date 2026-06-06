<?php

namespace App\Pdf;

use Barryvdh\DomPDF\Facade\Pdf;

class ActivityReportPdf
{
    public function __construct(
        private array $stats,
        private string $period
    ) {}

    public function generate(): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView(
            'pdf.activity-report',
            [
                'stats'       => $this->stats,
                'period'      => $this->period,
                'generatedAt' => now()->format('d/m/Y H:i'),
            ]
        )->setPaper('A4', 'portrait');
    }
}
