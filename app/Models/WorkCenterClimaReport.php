<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkCenterClimaReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_center_id',
        'title',
        'language',
        'storage_path',
        'original_filename',
        'mime_type',
        'file_size',
        'is_published',
        'is_active',
        'uploaded_by',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_published' => 'boolean',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileSizeHumanAttribute(): string
    {
        return number_format($this->file_size / 1024 / 1024, 2).' MB';
    }
}
