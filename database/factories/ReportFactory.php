<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'reporter_id'     => User::factory(),
            'reportable_type' => Property::class,
            'reportable_id'   => Property::factory(),
            'reason'          => fake()->randomElement([
                'contenu_inapproprie', 'arnaque_suspectee', 'informations_fausses', 'autre',
            ]),
            'description' => fake()->sentence(10),
            'status'      => 'en_attente',
            'admin_note'  => null,
            'handled_by'  => null,
            'handled_at'  => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state([
            'status'     => 'resolu',
            'handled_at' => now(),
            'admin_note' => 'Traité.',
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status'     => 'rejete',
            'handled_at' => now(),
            'admin_note' => 'Non fondé.',
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => 'en_cours']);
    }
}
