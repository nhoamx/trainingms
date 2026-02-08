<?php

namespace App\Models;

use App\Enums\WorkCenterType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class WorkCenter extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'slug',
        'type',
        'is_primary',
        'legal_name',
        'tax_id',
        'employer_registration',
        'street_address',
        'neighborhood',
        'postal_code',
        'municipality',
        'state',
        'phone',
        'emails',
        // Contact information
        'contact_name',
        'contact_position',
        'contact_email',
        'contact_phone',
        'responsible_name',
        'responsible_position',
        'responsible_email',
        'responsible_phone',
        // NOM-035 Census
        'total_workers',
        'total_men',
        'total_women',
        'sample_applied',
        'sample_men',
        'sample_women',
        'committee_members',
        'committee_men',
        'committee_women',
        'application_date',
        'sample_justification',
    ];

    protected function casts(): array
    {
        return [
            'type' => WorkCenterType::class,
            'is_primary' => 'boolean',
            'emails' => 'array',
            'application_date' => 'date',
            // Integer casts
            'total_workers' => 'integer',
            'total_men' => 'integer',
            'total_women' => 'integer',
            'sample_applied' => 'integer',
            'sample_men' => 'integer',
            'sample_women' => 'integer',
            'committee_members' => 'integer',
            'committee_men' => 'integer',
            'committee_women' => 'integer',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workCenter) {
            if (empty($workCenter->slug)) {
                $workCenter->slug = static::generateUniqueSlug(
                    $workCenter->name,
                    $workCenter->organization_id
                );
            }
        });
    }

    /**
     * Generate a unique slug for the work center within its organization
     */
    private static function generateUniqueSlug(string $name, string $organizationId, ?string $id = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (static::where('organization_id', $organizationId)
            ->where('slug', $slug)
            ->when($id, function ($query) use ($id) {
                return $query->where('id', '!=', $id);
            })->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get the organization that owns this work center
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get all paper evaluations for this work center
     */
    public function paperEvaluations(): HasMany
    {
        return $this->hasMany(PaperEvaluation::class);
    }

    /**
     * Get all quizzes for this work center
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * Get all occupation positions for this work center
     */
    public function occupationPositions(): HasMany
    {
        return $this->hasMany(OccupationPosition::class);
    }

    /**
     * Get all department areas for this work center
     */
    public function departmentAreas(): HasMany
    {
        return $this->hasMany(DepartmentArea::class);
    }

    /**
     * Scope to get only primary work centers
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to get work centers by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get full name (organization + center)
     */
    public function getFullNameAttribute(): string
    {
        return $this->organization->name.' - '.$this->name;
    }

    /**
     * Check if this is the primary work center
     */
    public function isPrimary(): bool
    {
        return $this->is_primary;
    }
}
