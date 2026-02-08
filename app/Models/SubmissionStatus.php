<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionStatus extends Model
{
    protected $fillable = [
        'folio',
        'personal_id',
        'organization_id',
        'work_center_id',
        'quiz_id',
        'status',
        'data_snapshot',
        'error_message',
        'processed_at',
        'retry_count',
        'session_id',
    ];

    protected $casts = [
        'data_snapshot' => 'array',
        'processed_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    // Status constants
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RETRYING = 'retrying';

    /**
     * Get the organization that owns the submission.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the quiz that owns the submission.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get the work center that owns the submission.
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    /**
     * Scope to get pending submissions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get failed submissions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Check if submission can be retried
     */
    public function canRetry(): bool
    {
        return $this->retry_count < 3 &&
               in_array($this->status, [self::STATUS_FAILED, self::STATUS_RETRYING]);
    }

    /**
     * Mark submission as processing
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    /**
     * Mark submission as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Mark submission as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }
}
