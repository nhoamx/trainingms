<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasUuids;
    protected $fillable = [
        'document_id',
        'folio',
        'organization_id',
        'data',
    ];

    protected $casts = [
        'data' => 'json',
    ];
}
