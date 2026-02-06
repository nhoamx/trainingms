<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_disabled',
        'temporal_password',
        'organization_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_disabled' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_disabled', false);
    }

    public function scopeDisabled($query)
    {
        return $query->where('is_disabled', true);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function workCenters()
    {
        return $this->belongsToMany(
            WorkCenter::class,
            'user_work_centers'
        )->withTimestamps();
    }

    /**
     * Obtiene los centros de trabajo a los que el usuario tiene acceso.
     * - admin: Todos los centros del sistema
     * - organization: Todos los centros de su organización
     * - work_center_user: Solo los centros asignados en pivot
     */
    public function accessibleWorkCenters()
    {
        // Admins tienen acceso global
        if ($this->hasRole('admin')) {
            return WorkCenter::query();
        }

        // Organization role ve todos los centros de su organización
        if ($this->hasRole('organization')) {
            return WorkCenter::where('organization_id', $this->organization_id);
        }

        // work_center_user ve solo centros asignados
        if ($this->hasRole('work_center_user')) {
            return $this->workCenters();
        }

        // Fallback: sin acceso
        return WorkCenter::whereRaw('1 = 0');
    }

    /**
     * Scope para filtrar queries por centros accesibles del usuario.
     * Uso: PaperEvaluation::forAccessibleCenters($user)->get()
     */
    public function scopeForAccessibleCenters($query, User $user)
    {
        if ($user->hasRole('admin')) {
            return $query; // Sin filtro
        }

        if ($user->hasRole('organization')) {
            $centerIds = WorkCenter::where('organization_id', $user->organization_id)
                ->pluck('id');

            return $query->whereIn('work_center_id', $centerIds);
        }

        if ($user->hasRole('work_center_user')) {
            $centerIds = $user->workCenters()->pluck('work_centers.id');

            return $query->whereIn('work_center_id', $centerIds);
        }

        return $query->whereRaw('1 = 0'); // Sin acceso
    }

    /**
     * Verifica si el usuario tiene acceso a un centro de trabajo específico.
     */
    public function hasAccessToWorkCenter(int|WorkCenter $workCenter): bool
    {
        $centerId = $workCenter instanceof WorkCenter ? $workCenter->id : $workCenter;

        return $this->accessibleWorkCenters()->where('id', $centerId)->exists();
    }

    public function getRoleNameAttribute()
    {
        return $this->roles->first()->name ?? 'No role assigned';
    }
}
