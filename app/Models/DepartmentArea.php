<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepartmentArea extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'organization_id',
        'identifier',
        'name',
    ];
    
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
