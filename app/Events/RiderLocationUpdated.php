<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiderLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $rider_id;
    public float $lat;
    public float $lng;
    public ?int $battery;
    public string $ts;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $rider_id,
        float $lat,
        float $lng,
        ?int $battery,
        $ts
    ) {
        $this->rider_id = $rider_id;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->battery = $battery;
        $this->ts = $ts->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('riders'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'rider.location.updated';
    }
}
