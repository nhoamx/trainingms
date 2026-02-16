<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfGenerationJob extends Model
{
    use HasUuids;

    protected $fillable = [
        'organization_id',
        'folio_batch_id',
        'guide_type',
        'total_folios',
        'processed_folios',
        'status',
        'file_paths',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'file_paths' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function folioBatch(): BelongsTo
    {
        return $this->belongsTo(FolioBatch::class);
    }

    /**
     * Get progress percentage for this job
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_folios <= 0) {
            return 0;
        }

        return (int) (($this->processed_folios / $this->total_folios) * 100);
    }

    /**
     * Mark job as started
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark job as completed
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'processed_folios' => $this->total_folios,
        ]);
    }

    /**
     * Mark job as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Add a file path to the list of generated files
     */
    public function addFilePath(string $path): void
    {
        $paths = $this->file_paths ?? [];
        $paths[] = $path;

        $this->update(['file_paths' => $paths]);
    }

    /**
     * Increment processed folios count
     */
    public function incrementProcessed(int $count): void
    {
        $this->increment('processed_folios', $count);
    }
}
