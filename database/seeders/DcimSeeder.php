<?php

namespace Database\Seeders;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Enums\Role;
use App\Models\Device;
use App\Models\Rack;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DcimSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the DCIM demo context: rack R03 in DC-Utrecht filled with the
     * servers and switch of customer MediCloud BV.
     */
    public function run(): void
    {
        $owner = User::where('email', 'klant@medicloud.test')->first();
        $technicus = User::where('role', Role::Technicus->value)->first();

        // The demo world belongs to the first technicus (Tessa); newly added
        // students start with an empty world of their own.
        $world = $technicus?->id;

        $rack = Rack::updateOrCreate(
            ['student_id' => $world, 'name' => 'R03'],
            ['location' => 'DC-Utrecht', 'height_u' => 42],
        );

        // medicloud-app01 is the predictive device: a positive metric_trend
        // makes its temperature climb each simulate:tick, crossing the
        // warning threshold (early signal) before the fault threshold.
        /** @var array<int, array{name: string, type: DeviceType, status: DeviceStatus, u_start: int, u_end: int, cpu: int, temp: int, metric_trend: int}> $devices */
        $devices = [
            ['name' => 'medicloud-app01', 'type' => DeviceType::Server, 'status' => DeviceStatus::Actief, 'u_start' => 1, 'u_end' => 2, 'cpu' => 25, 'temp' => 45, 'metric_trend' => 8],
            ['name' => 'medicloud-app02', 'type' => DeviceType::Server, 'status' => DeviceStatus::Waarschuwing, 'u_start' => 3, 'u_end' => 4, 'cpu' => 55, 'temp' => 68, 'metric_trend' => 0],
            ['name' => 'medicloud-db01', 'type' => DeviceType::Server, 'status' => DeviceStatus::Storing, 'u_start' => 5, 'u_end' => 8, 'cpu' => 72, 'temp' => 84, 'metric_trend' => 0],
            ['name' => 'medicloud-sw01', 'type' => DeviceType::Switch, 'status' => DeviceStatus::Offline, 'u_start' => 10, 'u_end' => 10, 'cpu' => 0, 'temp' => 0, 'metric_trend' => 0],
        ];

        foreach ($devices as $attributes) {
            $device = Device::updateOrCreate(
                ['student_id' => $world, 'rack_id' => $rack->id, 'name' => $attributes['name']],
                [...$attributes, 'owner_id' => $owner?->id],
            );

            $device->forceFill([
                'last_changed_at' => now(),
                'last_changed_by' => $technicus?->id,
            ])->saveQuietly();
        }

        // Seed an initial NOC alert so the alarm panel has history on load.
        $faulty = Device::where('name', 'medicloud-db01')->first();

        if ($faulty) {
            $alert = $faulty->alerts()->make([
                'from_status' => DeviceStatus::Waarschuwing->value,
                'to_status' => DeviceStatus::Storing->value,
                'message' => 'Waarschuwing → Storing (cpu 72%, temp 84°C)',
                'cpu' => 72,
                'temp' => 84,
            ]);
            $alert->student_id = $world;
            $alert->save();
        }
    }
}
