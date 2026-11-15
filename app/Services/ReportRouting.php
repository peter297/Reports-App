<?php

namespace App\Services;

use App\Models\ReportingAssignment;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves report recipients for a submitter from the configurable
 * reporting_assignments rules, snapshotted onto the report at submission.
 */
class ReportRouting
{
    /**
     * Explicit recipient users (union of routing rules matching the submitter).
     *
     * @return Collection<int, User>
     */
    public function recipientsFor(User $submitter): Collection
    {
        $roleIds = $submitter->roles->pluck('id');

        if ($roleIds->isEmpty()) {
            return collect();
        }

        $levelIds = $submitter->activeCoordinatorAssignments()
            ->pluck('academic_level_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $rules = ReportingAssignment::query()
            ->where('is_active', true)
            ->whereIn('submitter_role_id', $roleIds)
            ->where(function ($query) use ($submitter): void {
                $query->whereNull('branch_id')
                    ->when($submitter->branch_id, fn ($q) => $q->orWhere('branch_id', $submitter->branch_id), fn ($q) => $q->orWhereNull('branch_id'));
            })
            ->get()
            ->filter(function (ReportingAssignment $rule) use ($levelIds): bool {
                if ($rule->academic_level_id === null) {
                    return true;
                }

                // A level-scoped rule only applies when the submitter is assigned at that level.
                return in_array((int) $rule->academic_level_id, $levelIds, true);
            });

        $recipients = collect();

        foreach ($rules as $rule) {
            if ($rule->recipient_user_id) {
                $user = User::find($rule->recipient_user_id);
                if ($user) {
                    $recipients->push($user);
                }
            }

            if ($rule->recipient_role_id) {
                $recipients = $recipients->merge(
                    User::query()
                        ->where('is_active', true)
                        ->whereHas('roles', fn ($q) => $q->where('roles.id', $rule->recipient_role_id))
                        ->get()
                );
            }
        }

        return $recipients
            ->unique('id')
            ->reject(fn (User $user) => $user->id === $submitter->id)
            ->values();
    }

    /**
     * Expected submitters for a recipient role + period, used by the
     * submission tracking dashboards. Returns one row per expected user.
     *
     * @return Collection<int, array{user: User, branch: ?string, rule: ReportingAssignment}>
     */
    public function expectedSubmittersFor(int $recipientRoleId): Collection
    {
        $rules = ReportingAssignment::query()
            ->where('is_active', true)
            ->where('recipient_role_id', $recipientRoleId)
            ->get();

        $expected = collect();

        foreach ($rules as $rule) {
            if (! $rule->submitterRole) {
                continue;
            }

            $candidates = User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($q) => $q->where('roles.id', $rule->submitter_role_id))
                ->when($rule->branch_id, fn ($q) => $q->where('branch_id', $rule->branch_id))
                ->get()
                ->filter(function (User $candidate): bool {
                    // Coordinators/Deans are only expected to report when they hold at least
                    // one active scope assignment (dynamic expectation, never hardcoded).
                    if ($candidate->hasRole('Coordinator') || $candidate->hasRole('Dean of Islamic Studies')) {
                        return $candidate->activeCoordinatorAssignments()->exists();
                    }

                    return true;
                })
                ->values();

            foreach ($candidates as $candidate) {
                $expected->push([
                    'user' => $candidate,
                    'branch' => $rule->branch?->name,
                    'rule' => $rule,
                ]);
            }
        }

        return $expected;
    }
}
