<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EvaluationProcessingStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $status;

    public $message;

    public $finished;

    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct($status, $message, $finished = false, ?string $userId = null)
    {
        $this->status = $status;
        $this->message = $message;
        $this->finished = $finished;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Broadcast on a private channel specific to the user who started the operation.
        // If userId is not provided, fallback to the public channel for compatibility.
        if ($this->userId) {
            return [new PrivateChannel("evaluation-processing.{$this->userId}")];
        }

        return [new Channel('evaluation-processing')];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'evaluation.status';
    }
}
