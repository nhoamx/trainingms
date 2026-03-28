<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OrganizationConclusionsFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'slot',
        'title',
        'color',
        'disk',
        'path',
        'original_filename',
        'file_size',
        'mime_type',
        'is_published',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'slot' => 'integer',
            'file_size' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024) {
            return "{$bytes} B";
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / 1048576, 2).' MB';
    }

    public function getDownloadUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
