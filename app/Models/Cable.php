<?php

namespace App\Models;

use App\Enums\CableMedium;
use App\Models\Concerns\BelongsToStudent;
use Database\Factories\CableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

#[Fillable(['label', 'medium', 'color', 'from_device_id', 'from_port', 'to_device_id', 'to_port'])]
class Cable extends Model
{
    /** @use HasFactory<CableFactory> */
    use BelongsToStudent, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'medium' => CableMedium::class,
            'from_port' => 'integer',
            'to_port' => 'integer',
            'last_changed_at' => 'datetime',
        ];
    }

    /**
     * Stamp every change with who/when, so the cable schedule is verifiable
     * evidence (consistent with Device auto-logging).
     */
    protected static function booted(): void
    {
        static::saving(function (Cable $cable): void {
            $cable->last_changed_at = now();
            $cable->last_changed_by = Auth::id();
        });
    }

    /**
     * The device the cable starts at.
     *
     * @return BelongsTo<Device, $this>
     */
    public function fromDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'from_device_id');
    }

    /**
     * The device the cable ends at.
     *
     * @return BelongsTo<Device, $this>
     */
    public function toDevice(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'to_device_id');
    }

    /**
     * The user who last changed this cable.
     *
     * @return BelongsTo<User, $this>
     */
    public function lastChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_changed_by');
    }
}
