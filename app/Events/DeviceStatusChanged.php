<?php

namespace App\Events;

use App\Enums\DeviceStatus;
use App\Models\Device;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Device $device,
        public ?DeviceStatus $from = null,
    ) {}

    /**
     * Broadcast on the monitoring channel of the device's own student world,
     * so each student's NOC dashboard only receives its own updates.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel(self::channelFor($this->device->student_id)),
        ];
    }

    /**
     * The monitoring channel name for a given student world.
     */
    public static function channelFor(?int $studentId): string
    {
        return 'monitoring.'.($studentId ?? 'shared');
    }

    /**
     * The broadcast event name listened for by Echo.
     */
    public function broadcastAs(): string
    {
        return 'status.changed';
    }

    /**
     * The payload pushed to subscribed dashboards.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->device->id,
            'name' => $this->device->name,
            'status' => $this->device->status->value,
            'statusLabel' => $this->device->status->label(),
            'from' => $this->from?->value,
            'cpu' => $this->device->cpu,
            'temp' => $this->device->temp,
            'rack' => $this->device->rack?->name,
            'at' => now()->toIso8601String(),
        ];
    }
}
