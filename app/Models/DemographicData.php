<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemographicData extends Model
{
    /** @use HasFactory<\Database\Factories\DemographicDataFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'paper_evaluation_id',
        'gender',
        'age',
        'marital_status',
        'education_level',
        'position',
        'department',
        'position_type',
        'contract_type',
        'personnel_type',
        'work_schedule',
        'shift_rotation',
        'time_in_current_position',
        'work_experience',
        'extra_fields',
    ];

    protected function casts(): array
    {
        return [
            'extra_fields' => 'json',
        ];
    }

    /**
     * Get the paper evaluation that owns this demographic data
     */
    public function paperEvaluation(): BelongsTo
    {
        return $this->belongsTo(PaperEvaluation::class);
    }
}
