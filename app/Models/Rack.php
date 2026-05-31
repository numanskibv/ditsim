<?php

namespace App\Models;

use App\Models\Concerns\BelongsToStudent;
use Database\Factories\RackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'location', 'height_u'])]
class Rack extends Model
{
    /** @use HasFactory<RackFactory> */
    use BelongsToStudent, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'height_u' => 'integer',
        ];
    }

    /**
     * The devices mounted in this rack, ordered from the bottom U upwards.
     *
     * @return HasMany<Device, $this>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Device::class)->orderBy('u_start');
    }

    /**
     * Find the device occupying the given U position, if any.
     *
     * Relies on the devices relation already being loaded so the rack grid
     * can be rendered without an extra query per U slot.
     */
    public function deviceAt(int $u): ?Device
    {
        return $this->devices->first(
            fn (Device $device): bool => $u >= $device->u_start && $u <= $device->u_end,
        );
    }

    /**
     * The most recently changed device in this rack, used for the
     * "laatste wijziging" indicator on the rack overview.
     */
    public function lastChangedDevice(): ?Device
    {
        return $this->devices
            ->whereNotNull('last_changed_at')
            ->sortByDesc('last_changed_at')
            ->first();
    }
}
