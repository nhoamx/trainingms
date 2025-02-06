<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assessee extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'folio',
        'empresa',
        'datos',
    ];

    protected $casts = [
        'datos' => 'array',
    ];
}
