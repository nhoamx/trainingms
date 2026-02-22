<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkCenterSensitizationVideo extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_center_id',
        'title',
        'description',
        'audience',
        'storage_path',
        'original_filename',
        'mime_type',
        'file_size',
        'sort_order',
        'is_active',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function getFileSizeHumanAttribute(): string
    {
        return number_format($this->file_size / 1024 / 1024, 2).' MB';
    }
}
