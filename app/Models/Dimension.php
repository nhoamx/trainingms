<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Dimension extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'description', 'domain_id'];

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
}
