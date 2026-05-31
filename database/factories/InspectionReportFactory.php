<?php

namespace Database\Factories;

use App\Enums\InspectionStatus;
use App\Models\InspectionReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InspectionReport>
 */
class InspectionReportFactory extends Factory
{
    /**
     * Define the model's default state (all control points OK).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $items = [];
        foreach (InspectionReport::CONTROL_POINTS as $key => $label) {
            $items[] = [
                'key' => $key,
                'label' => $label,
                'status' => InspectionStatus::Ok->value,
                'observation' => '',
                'device_id' => null,
            ];
        }

        return [
            'inspector_id' => User::factory()->technicus(),
            'date' => now()->toDateString(),
            'items' => $items,
        ];
    }
}
