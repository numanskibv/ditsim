<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use App\Jobs\ApplyScenarioAction;
use App\Models\Scopes\StudentScope;
use App\Support\CurrentStudent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<int, array{delay: int, device_id?: int, device?: string, status: string}> $actions
 * @property array{rack: array<string, mixed>, devices: array<int, array<string, mixed>>}|null $blueprint
 */
#[Fillable(['name', 'description', 'actions', 'blueprint', 'created_by'])]
class Scenario extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actions' => 'array',
            'blueprint' => 'array',
        ];
    }

    /**
     * Whether this scenario can build a starting world from scratch.
     */
    public function isProvisioning(): bool
    {
        return ! empty($this->blueprint['devices'] ?? []);
    }

    /**
     * Build this scenario's blueprint world inside a student's world, then
     * schedule its timed actions there. Used to assign a starting scenario to
     * a student who begins empty.
     */
    public function applyTo(int $studentId): void
    {
        app(CurrentStudent::class)->runFor($studentId, function (): void {
            $this->provisionBlueprint();
        });

        $this->scheduleActionsFor($studentId);
    }

    /**
     * Create the rack and devices described by the blueprint in the currently
     * scoped world.
     */
    protected function provisionBlueprint(): void
    {
        if (! $this->isProvisioning()) {
            return;
        }

        $rack = Rack::create($this->blueprint['rack'] ?? [
            'name' => 'R01',
            'location' => 'DC-Sim',
            'height_u' => 42,
        ]);

        foreach ($this->blueprint['devices'] as $device) {
            $rack->devices()->create($device);
        }
    }

    /**
     * Schedule the timed status changes for a provisioned student world. Actions
     * reference devices by name (ids differ per student) and are resolved here.
     */
    protected function scheduleActionsFor(int $studentId): void
    {
        $devicesByName = Device::withoutGlobalScope(StudentScope::class)
            ->where('student_id', $studentId)
            ->pluck('id', 'name');

        foreach ($this->actions ?? [] as $action) {
            $name = $action['device'] ?? null;
            $deviceId = $name !== null ? ($devicesByName[$name] ?? null) : null;

            if ($deviceId === null) {
                continue;
            }

            ApplyScenarioAction::dispatch(
                (int) $deviceId,
                DeviceStatus::from($action['status']),
            )->delay(now()->addSeconds((int) $action['delay']));
        }
    }

    /**
     * Fire the scenario: each action is queued as a delayed job, so the
     * sequence plays out on the wall clock and broadcasts to the dashboard.
     */
    public function start(): void
    {
        foreach ($this->actions as $action) {
            ApplyScenarioAction::dispatch(
                (int) $action['device_id'],
                DeviceStatus::from($action['status']),
            )->delay(now()->addSeconds((int) $action['delay']));
        }
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
