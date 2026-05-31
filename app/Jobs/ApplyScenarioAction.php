<?php

namespace App\Jobs;

use App\Enums\DeviceStatus;
use App\Models\Device;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ApplyScenarioAction implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $deviceId,
        public DeviceStatus $status,
    ) {}

    /**
     * Apply the status to the device. The Device model's status-change hook
     * records an alert and broadcasts to the monitoring dashboard.
     */
    public function handle(): void
    {
        $device = Device::find($this->deviceId);

        $device?->update(['status' => $this->status]);
    }
}
