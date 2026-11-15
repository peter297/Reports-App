<?php

namespace App\Services;

use App\Models\SyllabusMapping;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Resolves the organizational academic data scope for a user.
 *
 * Scope kinds:
 *  - org      : CEO / Principal / Deputy Principal / Head Teacher / super_admin (all branches)
 *  - branch   : Administrator / Deputy Head Teacher (their branch, all levels)
 *  - assigned : Coordinator / Dean of Islamic Studies (their coordinator assignments)
 *  - own      : Teacher (assignments naming them)
 *  - none     : no academic data visibility (HR, Fleet, ICT, ...)
 */
class ReportScope
{
    private function __construct(
        public readonly User $user,
        public readonly string $kind,
        /** @var list<array{branch_id: int, academic_level_id: int}> */
        public readonly array $assignments,
        public readonly ?int $branchId,
    ) {
    }

    public static function for(User $user): self
    {
        if ($user->hasUnrestrictedAccess()) {
            return new self($user, 'org', [], null);
        }

        if ($user->hasRole('Coordinator') || $user->hasRole('Dean of Islamic Studies')) {
            $assignments = $user->coordinatorAssignments()
                ->where('is_active', true)
                ->get(['branch_id', 'academic_level_id'])
                ->map(fn ($row) => ['branch_id' => (int) $row->branch_id, 'academic_level_id' => (int) $row->academic_level_id])
                ->all();

            if ($assignments !== []) {
                return new self($user, 'assigned', $assignments, null);
            }

            // No assignments yet: fall back to branch-wide scope if a branch is set.
            return new self($user, 'assigned', $user->branch_id ? [['branch_id' => (int) $user->branch_id, 'academic_level_id' => 0]] : [], null);
        }

        if ($user->hasRole('Deputy Head Teacher') || $user->hasRole('Administrator')) {
            return new self($user, 'branch', [], $user->branch_id ? (int) $user->branch_id : null);
        }

        if ($user->hasRole('Teacher')) {
            return new self($user, 'own', [], null);
        }

        return new self($user, 'none', [], null);
    }

    /** @return list<int>|null  null = all branches */
    public function branchIds(): ?array
    {
        return match ($this->kind) {
            'org' => null,
            'branch' => $this->branchId ? [$this->branchId] : [],
            'assigned' => collect($this->assignments)->pluck('branch_id')->unique()->values()->all(),
            'own' => $this->ownBranchIds(),
            default => [],
        };
    }

    /** @return list<int>|null  null = all levels in scope */
    public function levelIds(): ?array
    {
        return match ($this->kind) {
            'assigned' => collect($this->assignments)->pluck('academic_level_id')->filter(fn ($id) => $id > 0)->unique()->values()->all() ?: null,
            default => null,
        };
    }

    public function isOrgWide(): bool
    {
        return $this->kind === 'org';
    }

    /** Human readable label for report headers, e.g. "Juja Road · Junior School". */
    public function label(): string
    {
        if ($this->kind === 'org') {
            return 'All branches';
        }

        if ($this->kind === 'none') {
            return 'No academic scope assigned';
        }

        $branchNames = \App\Models\Branch::whereIn('id', $this->branchIds() ?? [])->pluck('name');
        $levelIds = $this->levelIds();
        $levelNames = $levelIds === null ? collect() : \App\Models\AcademicLevel::whereIn('id', $levelIds)->pluck('name');

        $parts = [];
        if ($branchNames->isNotEmpty()) {
            $parts[] = $branchNames->implode(', ');
        }
        if ($levelNames->isNotEmpty()) {
            $parts[] = $levelNames->implode(', ');
        }

        return $parts === [] ? 'No academic scope assigned' : implode(' · ', $parts);
    }

    /** Subject IDs that belong to this scope (used for assessments, etc.). */
    public function mappingIds(): \Illuminate\Support\Collection
    {
        return $this->applyTo(SyllabusMapping::query())->pluck('syllabus_mappings.id');
    }

    /** Base mapping query limited to this scope (view level). */
    public function mappingsQuery(?Builder $query = null): Builder
    {
        $query ??= SyllabusMapping::query();

        return $this->applyTo($query);
    }

    /** Apply the scope constraints to an existing mapping query. */
    public function applyTo(Builder $query): Builder
    {
        if ($this->user->hasRole('Dean of Islamic Studies')) {
            $query->whereHas('subject', fn (Builder $subjectQuery) => $subjectQuery->where('name', 'like', '%islam%'));
        }

        if ($this->kind === 'org') {
            return $query;
        }

        $branchIds = $this->branchIds();
        $levelIds = $this->levelIds();

        if ($this->kind === 'own') {
            return $query->whereExists(function (\Illuminate\Database\Query\Builder $q): void {
                $q->selectRaw('1')
                    ->from('subject_assignments')
                    ->whereColumn('subject_assignments.subject_id', 'syllabus_mappings.subject_id')
                    ->whereColumn('subject_assignments.class_id', 'syllabus_mappings.class_id')
                    ->whereColumn('subject_assignments.year_session_id', 'syllabus_mappings.year_session_id')
                    ->where('subject_assignments.user_id', $this->user->id)
                    ->where('subject_assignments.assignment_role', 'teacher')
                    ->where(function (\Illuminate\Database\Query\Builder $q2): void {
                        $q2->whereNull('subject_assignments.stream_id')
                            ->orWhereColumn('subject_assignments.stream_id', 'syllabus_mappings.stream_id');
                    });
            });
        }

        if ($branchIds === []) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->kind === 'assigned') {
            return $query->where(function (Builder $outer): void {
                foreach ($this->assignments as $assignment) {
                    $outer->orWhere(function (Builder $pair) use ($assignment): void {
                        $pair->whereHas('class.branch', fn (Builder $b) => $b->where('branches.id', $assignment['branch_id']));
                        if ($assignment['academic_level_id'] > 0) {
                            $pair->whereHas('subject', fn (Builder $s) => $s->where('academic_level_id', $assignment['academic_level_id']));
                        }
                    });
                }
            });
        }

        // branch kind
        return $query->whereHas('class.branch', fn (Builder $b) => $b->whereIn('branches.id', $branchIds))
            ->when($levelIds !== null, fn (Builder $q) => $q->whereHas('subject', fn (Builder $s) => $s->whereIn('academic_level_id', $levelIds)));
    }

    /** @return list<int> */
    private function ownBranchIds(): array
    {
        return \App\Models\SubjectAssignment::query()
            ->join('classes', 'classes.id', '=', 'subject_assignments.class_id')
            ->where('subject_assignments.user_id', $this->user->id)
            ->distinct()
            ->pluck('classes.branch_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
