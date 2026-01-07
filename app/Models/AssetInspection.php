<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetInspection extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'asset_id',
        'inspector_name',
        'inspection_date',
        'extinguisher_weight',
        'checklist_results',
        'anomalies_followup',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
            'checklist_results' => 'array',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
