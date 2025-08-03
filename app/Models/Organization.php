<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'logo',
        'folio_organization'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }
    
    public function occupationPositions()
    {
        return $this->hasMany(OccupationPosition::class);
    }
    
    public function departmentAreas()
    {
        return $this->hasMany(DepartmentArea::class);
    }

    public function folioBatches()
    {
        return $this->hasMany(FolioBatch::class);
    }
}
