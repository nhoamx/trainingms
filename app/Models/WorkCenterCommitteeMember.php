<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkCenterCommitteeMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_center_id',
        'name',
        'department_area',
        'position',
        'factor',
    ];

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }
}
