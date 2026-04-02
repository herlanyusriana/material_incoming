<?php

namespace App\Events\Production;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitoringUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $type,
        public array $dates = [],
        public array $machine_ids = [],
        public array $order_ids = [],
        public array $meta = [],
        public string $emitted_at = '',
    ) {
        $this->dates = array_values(array_unique(array_filter($this->dates)));
        $this->machine_ids = array_values(array_unique(array_filter(array_map('intval', $this->machine_ids))));
        $this->order_ids = array_values(array_unique(array_filter(array_map('intval', $this->order_ids))));
        $this->emitted_at = $this->emitted_at ?: now()->toDateTimeString();
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('production.monitoring'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'production.monitoring.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'dates' => $this->dates,
            'machine_ids' => $this->machine_ids,
            'order_ids' => $this->order_ids,
            'meta' => $this->meta,
            'emitted_at' => $this->emitted_at,
        ];
    }
}
