<?php

namespace App\Models;

use App\Helpers\Utils;
use App\Support\AcademicSections;
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
        'branch_id',
        'line_manager_id',
        'is_active',
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
            'is_active' => 'boolean',
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

    public function reportingRelationships(): HasMany
    {
        return $this->hasMany(ReportingRelationship::class);
    }

    public function managerRelationships(): HasMany
    {
        return $this->hasMany(ReportingRelationship::class, 'manager_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function coordinatorAssignments(): HasMany
    {
        return $this->hasMany(CoordinatorAssignment::class);
    }

    public function activeCoordinatorAssignments(): HasMany
    {
        return $this->coordinatorAssignments()->where('is_active', true);
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(SubjectAssignment::class);
    }

    /**
     * Branch IDs the user is authorized to manage data in:
     * their own branch (DHT/Administrator) union their coordinator assignments.
     *
     * @return list<int>
     */
    public function accessibleBranchIds(): array
    {
        $ids = collect();

        if ($this->branch_id) {
            $ids->push((int) $this->branch_id);
        }

        $ids = $ids->merge($this->activeCoordinatorAssignments()->pluck('branch_id'));

        return $ids->unique()->filter()->map(fn ($id) => (int) $id)->values()->all();
    }

    /** @return list<string> */
    public function accessibleBranchNames(): array
    {
        return \App\Models\Branch::whereIn('id', $this->accessibleBranchIds())->pluck('name')->all();
    }

    /**
     * Whether the user is the super administrator (full system configuration).
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRoleSafe('super_admin');
    }

    /**
     * Org-wide data visibility: super admin plus the executive/senior leadership tier.
     * This does NOT grant system configuration access (see isSuperAdmin()).
     */
    public function hasUnrestrictedAccess(): bool
    {
        return $this->hasAnyRole([
            'super_admin',
            'CEO',
            'Principal',
            'Deputy Principal',
            'Head Teacher',
        ]);
    }

    /**
     * System configuration access (roles, settings, structure, templates).
     */
    public function hasSystemAccess(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Whether the user may manage the syllabus structure and all mappings.
     * Kept for compatibility with the syllabus module.
     */
    public function hasSyllabusManagementAccess(): bool
    {
        return $this->hasSystemAccess();
    }

    /**
     * Whether the user is a coordinator/dean with at least one assignment.
     */
    public function hasSyllabusCoordinatorScope(): bool
    {
        return ($this->hasRole('Coordinator') || $this->hasRole('Dean of Islamic Studies'))
            && $this->activeCoordinatorAssignments()->exists();
    }

    /**
     * Whether the user may see every syllabus mapping in the school.
     */
    public function hasSyllabusViewAllAccess(): bool
    {
        return $this->hasUnrestrictedAccess()
            || $this->hasRole('Deputy Head Teacher')
            || $this->hasSyllabusCoordinatorScope();
    }

    /**
     * Branch names this user may act in.
     *
     * @return array<int, string>
     */
    public function assignableBranchNames(): array
    {
        if ($this->hasUnrestrictedAccess()) {
            return Branch::orderBy('name')->pluck('name')->all();
        }

        $names = $this->coordinatedBranches();

        if ($this->hasRole('Deputy Head Teacher') && ($branchName = $this->branch?->name)) {
            $names[] = $branchName;
        }

        return array_values(array_unique($names));
    }

    /**
     * Section select options (value => label) for the attendance/class-size forms.
     *
     * @return array<string, string>
     */
    public function assignableSectionCodes(?string $branchName = null): array
    {
        $all = ['EYE' => 'EYE - Early Years Education', 'Upper Primary' => 'Upper Primary', 'Junior School' => 'Junior School'];

        if ($this->hasUnrestrictedAccess()) {
            return $all;
        }

        $options = [];

        if ($this->hasRole('Deputy Head Teacher')) {
            if (! $branchName || $branchName === $this->branch?->name) {
                return $all;
            }

            return [];
        }

        $assignments = $this->activeCoordinatorAssignments()->with('branch', 'academicLevel')->get();

        foreach ($assignments as $assignment) {
            if ($branchName !== null && $assignment->branch?->name !== $branchName) {
                continue;
            }

            $code = AcademicSections::codeFor($assignment->academicLevel?->name);

            if ($code !== null) {
                $options[$code] = AcademicSections::labelFor($code);
            }
        }

        return $options;
    }

    /**
     * Distinct branches the user is assigned to coordinate (branch names).
     *
     * @return array<int, string>
     */
    public function coordinatedBranches(): array
    {
        return \App\Models\Branch::whereIn(
            'id',
            $this->activeCoordinatorAssignments()->select('branch_id')->distinct()->pluck('branch_id')
        )->pluck('name')->all();
    }

    /**
     * Distinct academic level names the user is assigned to coordinate.
     *
     * @return array<int, string>
     */
    public function coordinatedSections(): array
    {
        return \App\Models\AcademicLevel::whereIn(
            'id',
            $this->activeCoordinatorAssignments()->select('academic_level_id')->distinct()->pluck('academic_level_id')
        )->pluck('name')->all();
    }

    /**
     * Whether a coordinator/dean may see the given mapping: the mapping's
     * subject level is one of their assigned levels in the assigned branch
     * (Deans of Islamic Studies are limited to Islamic Studies subjects).
     */
    public function coordinatesSyllabusMapping(\App\Models\SyllabusMapping $mapping): bool
    {
        if (! ($this->hasRole('Coordinator') || $this->hasRole('Dean of Islamic Studies'))) {
            return false;
        }

        if ($this->hasRole('Dean of Islamic Studies')
            && ! str_contains(mb_strtolower((string) $mapping->subject?->name), 'islam')) {
            return false;
        }

        $assignments = $this->activeCoordinatorAssignments()->get(['branch_id', 'academic_level_id']);

        if ($assignments->isEmpty()) {
            return false;
        }

        $mappingBranchId = $mapping->class?->branch_id;
        $mappingLevelId = $mapping->subject?->academic_level_id;

        foreach ($assignments as $assignment) {
            $branchMatches = (int) $assignment->branch_id === (int) $mappingBranchId;
            $levelMatches = (int) $assignment->academic_level_id === 0
                || (int) $assignment->academic_level_id === (int) $mappingLevelId;

            if ($branchMatches && $levelMatches) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the user may see the given mapping.
     */
    public function isAssignedToMapping(\App\Models\SyllabusMapping $mapping): bool
    {
        if ($this->hasSyllabusViewAllAccess()) {
            return true;
        }

        if ($this->coordinatesSyllabusMapping($mapping)) {
            return true;
        }

        return $this->subjectAssignments()
            ->where('subject_id', $mapping->subject_id)
            ->where('class_id', $mapping->class_id)
            ->where('year_session_id', $mapping->year_session_id)
            ->where(fn ($query) => $mapping->stream_id
                ? $query->where(fn ($q) => $q->whereNull('stream_id')->orWhere('stream_id', $mapping->stream_id))
                : $query->whereNull('stream_id'))
            ->exists();
    }

    /**
     * Whether the user may record attendance for the given branch/section/class/stream.
     */
    public function canManageAttendance(string $branch, string $section, ?int $classId = null, ?int $streamId = null): bool
    {
        if ($this->hasUnrestrictedAccess()) {
            return true;
        }

        $branchModel = \App\Models\Branch::where('name', $branch)->first();
        if (! $branchModel) {
            return false;
        }

        $levelName = AcademicSections::canonical($section);
        $levelId = $levelName ? \App\Models\AcademicLevel::where('name', $levelName)->value('id') : null;

        // Deputy Head Teacher: branch-wide attendance oversight.
        if ($this->hasRole('Deputy Head Teacher')
            && (int) $this->branch_id === (int) $branchModel->id) {
            return $this->classStreamIsValid($branchModel->id, $levelId, $classId, $streamId);
        }

        if (! ($this->hasRole('Coordinator') || $this->hasRole('Dean of Islamic Studies'))) {
            return false;
        }

        $entitled = $this->activeCoordinatorAssignments()
            ->where('branch_id', $branchModel->id)
            ->get(['academic_level_id'])
            ->contains(fn ($a) => (int) $a->academic_level_id === 0 || (int) $a->academic_level_id === (int) $levelId);

        if (! $entitled) {
            return false;
        }

        return $this->classStreamIsValid($branchModel->id, $levelId, $classId, $streamId);
    }

    protected function classStreamIsValid(int $branchId, ?int $levelId, ?int $classId, ?int $streamId): bool
    {
        if ($streamId) {
            return \App\Models\Stream::query()
                ->join('classes', 'classes.id', '=', 'streams.class_id')
                ->where('streams.id', $streamId)
                ->where('classes.branch_id', $branchId)
                ->when($levelId, fn ($q) => $q->where('classes.academic_level_id', $levelId))
                ->when($classId, fn ($q) => $q->where('classes.id', $classId))
                ->exists();
        }

        if ($classId) {
            return \App\Models\Classes::query()
                ->whereKey($classId)
                ->where('branch_id', $branchId)
                ->when($levelId, fn ($q) => $q->where('academic_level_id', $levelId))
                ->exists();
        }

        return true;
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
                if (\Spatie\Permission\Models\Role::query()->where('name', $defaultRole)->where('guard_name', 'web')->exists()) {
                    $user->assignRole($defaultRole);
                }
            } catch (\Exception $e) {
                report($e);
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $specRoles = [
            'super_admin',
            'CEO',
            'Principal',
            'Deputy Principal',
            'Head Teacher',
            'Deputy Head Teacher',
            'Coordinator',
            'Dean of Islamic Studies',
            'Administrator',
            'ICT Officer',
            'HR Officer',
            'Fleet Manager',
            'Teacher',
        ];

        if (in_array($panel->getId(), ['admin', 'staff', 'teacher', 'app'], true)) {
            return collect($specRoles)->contains(fn (string $role) => $this->hasRoleSafe($role));
        }

        return false;
    }

    /**
     * hasRole() throws when the role does not exist; treat a missing role as not held.
     */
    public function hasRoleSafe(string $role): bool
    {
        try {
            return $this->hasRole($role);
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist) {
            return false;
        }
    }

    public function scopeExcludeCurrentUser($query, $currentUserId)
    {
        return $query->where('id', '!=', $currentUserId);
    }
}
