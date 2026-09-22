<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Helpers\Utils;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, HasPanelShield, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'branch',
        'line_manager_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
        ];
    }

    public function lineManager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'line_manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'line_manager_id');
    }

    public function sectionCoordinatorAssignments(): HasMany
    {
        return $this->hasMany(SectionCoordinatorAssignment::class);
    }

    public function canManageAttendance(string $branch, string $section, ?int $classId = null, ?int $streamId = null): bool
    {
        if ($this->hasUnrestrictedAccess()) {
            return true;
        }

        if (! ($this->hasRole('Coordinators') && $this->sectionCoordinatorAssignments()
            ->where('branch', $branch)
            ->where('section', $section)
            ->exists())) {
            return false;
        }

        if ($classId && ! Stream::query()
            ->where('branch', $branch)
            ->where('section', $section)
            ->where('class_id', $classId)
            ->exists()) {
            return false;
        }

        if ($streamId) {
            $streamQuery = Stream::query()
                ->where('branch', $branch)
                ->where('section', $section)
                ->whereKey($streamId);

            if ($classId) {
                $streamQuery->where('class_id', $classId);
            }

            if (! $streamQuery->exists()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Branches the user is assigned to coordinate.
     *
     * @return array<int, string>
     */
    public function coordinatedBranches(): array
    {
        return $this->sectionCoordinatorAssignments()
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch')
            ->all();
    }

    public function hasUnrestrictedAccess(): bool
    {
        return $this->hasAnyRole([
            'super_admin',
            'CEO',
            'Principal',
            'HeadTeacher',
            'ICT Department',
            'Admin',
        ]);
    }

    protected static function booted(): void
    {
        static::created(function (User $user) {
            if ($user->roles()->exists()) {
                return;
            }

            $defaultRole = config('filament-shield.app_user.name', 'app_user');

            if (! config('filament-shield.app_user.enabled', false)) {
                return;
            }

            try {
                $user->assignRole($defaultRole);
            } catch (\Exception $e) {
                report($e);
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasUnrestrictedAccess()
                || $this->hasRole(Utils::getSuperAdminName())
                || $this->hasRole(config('filament-shield.app_user.name', 'app_user'))
                || $this->hasRole(config('filament-shield.staff_user.name', 'staff_user'))
                || $this->hasRole(config('filament-shield.teacher_user.name', 'teacher_user'));
        }

        if ($panel->getId() === 'app') {
            return $this->hasRole(Utils::getSuperAdminName())
                || $this->hasRole(config('filament-shield.app_user.name', 'app_user'));
        }

        if ($panel->getId() === 'staff') {
            return $this->hasRole(Utils::getSuperAdminName())
                || $this->hasRole(config('filament-shield.staff_user.name', 'staff_user'));
        }

        if ($panel->getId() === 'teacher') {
            return $this->hasRole(Utils::getSuperAdminName())
                || $this->hasRole(config('filament-shield.teacher_user.name', 'teacher_user'));
        }

        return false;
    }

    public function scopeExcludeCurrentUser($query, $currentUserId)
    {
        return $query->where('id', '!=', $currentUserId);
    }
}
