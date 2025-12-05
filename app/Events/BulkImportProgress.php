<?php

namespace App\Events;

use App\Models\BulkImportJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BulkImportProgress implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public BulkImportJob $bulkImportJob
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('bulk-import.'.$this->bulkImportJob->user_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->bulkImportJob->id,
            'status' => $this->bulkImportJob->status,
            'total_rows' => $this->bulkImportJob->total_rows,
            'processed_rows' => $this->bulkImportJob->processed_rows,
            'updated_count' => $this->bulkImportJob->updated_count,
            'skipped_count' => $this->bulkImportJob->skipped_count,
            'progress_percentage' => $this->bulkImportJob->getProgressPercentage(),
            'errors' => $this->bulkImportJob->errors ?? [],
            'error_message' => $this->bulkImportJob->error_message,
            'file_name' => $this->bulkImportJob->file_name,
        ];
    }
}
