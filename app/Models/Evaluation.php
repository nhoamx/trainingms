<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
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
