<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Events\DeviceStatusChanged;
use App\Models\Concerns\BelongsToStudent;
use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

#[Fillable(['rack_id', 'owner_id', 'name', 'type', 'status', 'u_start', 'u_end', 'port_count', 'cpu', 'temp', 'metric_trend'])]
class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use BelongsToStudent, HasFactory;

    /** Temperature (°C) at which a device is flagged early / failing. */
    public const TEMP_WARN = 65;

    public const TEMP_FAULT = 80;

    /** CPU load (%) at which a device is flagged early / failing. */
    public const CPU_WARN = 85;

    public const CPU_FAULT = 95;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DeviceType::class,
            'status' => DeviceStatus::class,
            'u_start' => 'integer',
            'u_end' => 'integer',
            'port_count' => 'integer',
            'cpu' => 'integer',
            'temp' => 'integer',
            'metric_trend' => 'integer',
            'last_changed_at' => 'datetime',
        ];
    }

    /**
     * Stamp every change with who/when, and on a status change record an
     * alert and broadcast the new state to subscribed NOC dashboards.
     */
    protected static function booted(): void
    {
        static::saving(function (Device $device): void {
            $device->last_changed_at = now();
            $device->last_changed_by = Auth::id();
        });

        static::updated(function (Device $device): void {
            if (! $device->wasChanged('status')) {
                return;
            }

            $from = DeviceStatus::tryFrom((string) $device->getRawOriginal('status'));

            if ($device->status->isAlerting()) {
                $alert = $device->alerts()->make([
                    'from_status' => $from?->value,
                    'to_status' => $device->status->value,
                    'message' => sprintf(
                        '%s → %s (cpu %d%%, temp %d°C)',
                        $from?->label() ?? '—',
                        $device->status->label(),
                        $device->cpu,
                        $device->temp,
                    ),
                    'cpu' => $device->cpu,
                    'temp' => $device->temp,
                ]);

                // The alert lives in the same world as the device it concerns.
                $alert->student_id = $device->student_id;
                $alert->save();
            }

            DeviceStatusChanged::dispatch($device, $from);
        });
    }

    /**
     * Derive the status implied by the current metrics. Fault thresholds win
     * over warning thresholds; below both the device is healthy.
     */
    public static function statusFromMetrics(int $cpu, int $temp): DeviceStatus
    {
        if ($temp >= self::TEMP_FAULT || $cpu >= self::CPU_FAULT) {
            return DeviceStatus::Storing;
        }

        if ($temp >= self::TEMP_WARN || $cpu >= self::CPU_WARN) {
            return DeviceStatus::Waarschuwing;
        }

        return DeviceStatus::Actief;
    }

    /**
     * The rack this device is mounted in.
     *
     * @return BelongsTo<Rack, $this>
     */
    public function rack(): BelongsTo
    {
        return $this->belongsTo(Rack::class);
    }

    /**
     * The customer that owns this device.
     *
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * The user who last changed this device.
     *
     * @return BelongsTo<User, $this>
     */
    public function lastChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_changed_by');
    }

    /**
     * The alerts raised for this device, newest first.
     *
     * @return HasMany<DeviceAlert, $this>
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(DeviceAlert::class)->latest();
    }

    /**
     * The number of U positions this device occupies.
     */
    public function heightInU(): int
    {
        return $this->u_end - $this->u_start + 1;
    }
}
