<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class EvaluationCompletedNotification extends Notification implements ShouldBroadcastNow, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $folio,
        public string $personalId,
        public ?string $organizationId = null,
        public ?string $workCenterId = null,
        public ?string $organizationName = null,
        public ?string $workCenterName = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification for the database.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'evaluation_completed',
            'folio' => $this->folio,
            'personal_id' => $this->personalId,
            'organization_id' => $this->organizationId,
            'work_center_id' => $this->workCenterId,
            'organization_name' => $this->organizationName,
            'work_center_name' => $this->workCenterName,
            'title' => 'Evaluación completada',
            'message' => $this->getMessage(),
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'evaluation_completed',
            'folio' => $this->folio,
            'personal_id' => $this->personalId,
            'organization_id' => $this->organizationId,
            'work_center_id' => $this->workCenterId,
            'organization_name' => $this->organizationName,
            'work_center_name' => $this->workCenterName,
            'title' => 'Evaluación completada',
            'message' => $this->getMessage(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get the notification message
     */
    private function getMessage(): string
    {
        $parts = [];

        $parts[] = "Se ha completado la evaluación con folio {$this->folio}";

        if ($this->workCenterName) {
            $parts[] = "en {$this->workCenterName}";
        } elseif ($this->organizationName) {
            $parts[] = "en {$this->organizationName}";
        }

        return implode(' ', $parts).'.';
    }
}
