<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use App\Helpers\Utils;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, HasPanelShield;

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

    public function canManageAttendance(string $branch, string $section): bool
    {
        return $this->hasUnrestrictedAccess()
            || ($this->hasRole('Coordinators') && $this->sectionCoordinatorAssignments()
                ->where('branch', $branch)
                ->where('section', $section)
                ->exists());
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
        // Check and create the staff_user role
        if (config('filament-shield.staff_user.enabled', false)) {
            FilamentShield::createRole(name: config('filament-shield.staff_user.name', 'staff_user'));

            static::created(function (User $user) {
                $user->assignRole(config('filament-shield.staff_user.name', 'staff_user'));
            });

            static::deleting(function (User $user) {
                $user->removeRole(config('filament-shield.staff_user.name', 'staff_user'));
            });
        }

        // Check and create the app_user role
        if (config('filament-shield.app_user.enabled', false)) {
            FilamentShield::createRole(name: config('filament-shield.app_user.name', 'app_user'));

            static::created(function (User $user) {
                $user->assignRole(config('filament-shield.app_user.name', 'app_user'));
            });

            static::deleting(function (User $user) {
                $user->removeRole(config('filament-shield.app_user.name', 'app_user'));
            });
        }

        // Check and create the teacher_user role
        if (config('filament-shield.teacher_user.enabled', false)) {
            FilamentShield::createRole(name: config('filament-shield.teacher_user.name', 'teacher_user'));

            static::created(function (User $user) {
                $user->assignRole(config('filament-shield.teacher_user.name', 'teacher_user'));
            });

            static::deleting(function (User $user) {
                $user->removeRole(config('filament-shield.teacher_user.name', 'teacher_user'));
            });
        }
    }
    


    public function canAccessPanel(Panel $panel): bool
    {
        if($panel->getId() == 'admin'){
            return $this->hasRole(Utils::getSuperAdminName()) || $this->hasRole(config
            ('filament-shield.app_user.name')) || $this->hasRole(config
            ('filament-shield.staff_user.name')) || $this->hasRole(config
            ('filament-shield.teacher_user.name'));
        }
        elseif($panel->getId() == 'app'){
            return $this->hasRole(Utils::getSuperAdminName()) || $this->hasRole(config('filament-shield.app_user.name'));
        }
        elseif($panel->getId() == 'staff'){
            return $this->hasRole(Utils::getSuperAdminName()) || $this->hasRole(config('filament-shield.staff_user.name'));           
        }
        elseif($panel->getId() == 'teacher'){
            return $this->hasRole(Utils::getSuperAdminName()) || $this->hasRole(config('filament-shield.teacher_user.name'));           
        }

        else{
            return false;
        }
            
    }

    public function scopeExcludeCurrentUser($query, $currentUserId)
    {
        return $query->where('id', '!=', $currentUserId);
    }


}
