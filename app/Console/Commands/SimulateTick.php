<?php

namespace App\Console\Commands;

use App\Enums\DeviceStatus;
use App\Models\Device;
use App\Models\Scopes\StudentScope;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('simulate:tick {--device= : Only simulate the device with this id} {--student= : Only simulate the world of this student id}')]
#[Description('Mutate device metrics (cpu/temp); cross a threshold to change status and broadcast it.')]
class SimulateTick extends Command
{
    /**
     * Execute the console command.
     *
     * With no options every world is ticked (the scheduled run); --student
     * limits it to one student's world (the manual dashboard button).
     */
    public function handle(): int
    {
        // A system operation: tick across worlds regardless of who triggered
        // it, scoped only by the explicit --student option when given.
        $devices = Device::withoutGlobalScope(StudentScope::class)
            ->where('status', '!=', DeviceStatus::Offline->value)
            ->when($this->option('device'), fn ($query, $id) => $query->whereKey($id))
            ->when($this->option('student'), fn ($query, $studentId) => $query->where('student_id', $studentId))
            ->get();

        $changed = 0;

        foreach ($devices as $device) {
            [$cpu, $temp] = $this->nextMetrics($device);
            $previousStatus = $device->status;
            $newStatus = Device::statusFromMetrics($cpu, $temp);

            $device->update([
                'cpu' => $cpu,
                'temp' => $temp,
                'status' => $newStatus,
            ]);

            if ($newStatus !== $previousStatus) {
                $changed++;
                $this->line(sprintf(
                    '  %s: %s → <options=bold>%s</> (cpu %d%%, temp %d°C)',
                    $device->name,
                    $previousStatus->label(),
                    $newStatus->label(),
                    $cpu,
                    $temp,
                ));
            }
        }

        $this->info(sprintf('Tick verwerkt: %d device(s), %d statuswijziging(en).', $devices->count(), $changed));

        return self::SUCCESS;
    }

    /**
     * Compute the next cpu/temp for a device. Devices with a positive
     * metric_trend climb deterministically (predictive early-warning demo);
     * the rest drift randomly within healthy bounds.
     *
     * @return array{0: int, 1: int}
     */
    protected function nextMetrics(Device $device): array
    {
        if ($device->metric_trend > 0) {
            $temp = min($device->temp + $device->metric_trend, 120);
            $cpu = max(0, min(100, $device->cpu + random_int(-3, 6)));

            return [$cpu, $temp];
        }

        $temp = max(25, min(110, $device->temp + random_int(-4, 4)));
        $cpu = max(5, min(100, $device->cpu + random_int(-8, 8)));

        return [$cpu, $temp];
    }
}
