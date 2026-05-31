<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use App\Models\Concerns\BelongsToStudent;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['from_status', 'to_status', 'message', 'cpu', 'temp'])]
class DeviceAlert extends Model
{
    use BelongsToStudent;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => DeviceStatus::class,
            'to_status' => DeviceStatus::class,
            'cpu' => 'integer',
            'temp' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Device, $this>
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
