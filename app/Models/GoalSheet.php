<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalSheet extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_SUBMITTED => 'Submitted',
        self::STATUS_ACKNOWLEDGED => 'Acknowledged',
    ];

    protected $fillable = [
        'goal_sheet_category_id', 'staff_user_id', 'staff_name', 'job_title', 'department',
        'goal_year', 'coordinator_id', 'coordinator_name', 'line_manager_2_id', 'line_manager_2_name',
        'status', 'submitted_at', 'end_of_year_rating', 'notes',
    ];

    protected $casts = [
        'goal_year' => 'integer',
        'submitted_at' => 'datetime',
        'end_of_year_rating' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(GoalSheetCategory::class, 'goal_sheet_category_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function lineManager2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'line_manager_2_id');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(GoalSheetGoal::class)->orderBy('position')->orderBy('goal_number');
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(GoalSheetAcknowledgement::class);
    }

    /** Sum of performance weights across goals (workbook's SUM row — should total 1.00). */
    public function weightTotal(): float
    {
        return round((float) $this->goals->sum(fn (GoalSheetGoal $goal) => (float) $goal->performance_weight), 4);
    }

    /**
     * Calculated weighted rating for the sheet (%). Documented, see GOAL_SHEETS.md:
     * for every goal with recorded checkpoint data, attainment% = average of
     * (actual vs the target of the term the checkpoint belongs to; HB: actual/target,
     * LB: target/actual). Sheet rating = sum(perf_weight × attainment) over goals with data.
     * Returns null when no goal has any attainment data.
     */
    public function calculatedWeightedRating(): ?float
    {
        $total = 0.0;
        $hasData = false;

        foreach ($this->goals as $goal) {
            $attainment = $goal->attainment();

            if ($attainment === null) {
                continue;
            }

            $hasData = true;
            $total += (float) ($goal->performance_weight ?? 0) * $attainment;
        }

        return $hasData ? round($total, 2) : null;
    }

    /** Sum of performance weights of the goals that have attainment data. */
    public function ratedWeightTotal(): float
    {
        return round((float) $this->goals
            ->filter(fn (GoalSheetGoal $goal) => $goal->attainment() !== null)
            ->sum(fn (GoalSheetGoal $goal) => (float) $goal->performance_weight), 4);
    }

    public function isLocked(): bool
    {
        return $this->status !== self::STATUS_DRAFT;
    }

    public function isEditableBy(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($this->status !== self::STATUS_DRAFT) {
            return false;
        }

        return (int) $this->staff_user_id === (int) $user->id
            || $user->hasUnrestrictedAccess();
    }

    public function isWritableBy(User $user): bool
    {
        return $this->isEditableBy($user);
    }

    /** Whether the user may view this sheet at all (interface AND backend gate). */
    public function isAccessibleBy(User $user): bool
    {
        if ($user->hasUnrestrictedAccess()) {
            return true;
        }

        return (int) $this->staff_user_id === (int) $user->id
            || (int) $this->coordinator_id === (int) $user->id
            || (int) $this->line_manager_2_id === (int) $user->id;
    }

    /** Query scope: sheets this user may view. */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasUnrestrictedAccess()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('staff_user_id', $user->id)
                ->orWhere('coordinator_id', $user->id)
                ->orWhere('line_manager_2_id', $user->id);
        });
    }

    public function acknowledgementFor(string $party): ?GoalSheetAcknowledgement
    {
        return $this->acknowledgements->firstWhere('party', $party);
    }

    public function isSignedBy(string $party): bool
    {
        return (bool) ($this->acknowledgementFor($party)?->signed);
    }

    public function pendingAcknowledgement(): bool
    {
        return $this->status === self::STATUS_SUBMITTED
            && (! $this->isSignedBy(GoalSheetAcknowledgement::PARTY_LINE_MANAGER_1)
                || ! $this->isSignedBy(GoalSheetAcknowledgement::PARTY_LINE_MANAGER_2));
    }

    /** Which party the given user can acknowledge as (owner | line_manager_1 | line_manager_2 | null). */
    public function acknowledgementPartyFor(User $user): ?string
    {
        if ((int) $this->staff_user_id === (int) $user->id) {
            return GoalSheetAcknowledgement::PARTY_OWNER;
        }

        if ((int) $this->coordinator_id === (int) $user->id
            || (($this->coordinator_id === null) && $user->hasUnrestrictedAccess())) {
            return GoalSheetAcknowledgement::PARTY_LINE_MANAGER_1;
        }

        if ((int) $this->line_manager_2_id === (int) $user->id
            || (($this->line_manager_2_id === null) && $user->hasUnrestrictedAccess())) {
            return GoalSheetAcknowledgement::PARTY_LINE_MANAGER_2;
        }

        return null;
    }

    /** Called when an acknowledgement is stored; auto-advances status. */
    public function recordAcknowledgement(string $party, User $signer, ?string $signaturePath = null, ?string $notes = null): GoalSheetAcknowledgement
    {
        $acknowledgement = GoalSheetAcknowledgement::updateOrCreate(
            ['goal_sheet_id' => $this->id, 'party' => $party],
            [
                'signer_user_id' => $signer->id,
                'signer_name' => $signer->name,
                'signed' => true,
                'signature_path' => $signaturePath,
                'signed_at' => now(),
                'notes' => $notes,
            ]
        );

        $this->refresh();

        if ($this->isSignedBy(GoalSheetAcknowledgement::PARTY_LINE_MANAGER_1)
            && $this->isSignedBy(GoalSheetAcknowledgement::PARTY_LINE_MANAGER_2)) {
            $this->forceFill(['status' => self::STATUS_ACKNOWLEDGED])->saveQuietly();
        }

        return $acknowledgement;
    }

    public function submit(User $submitter): void
    {
        $this->forceFill([
            'status' => self::STATUS_SUBMITTED,
            'submitted_at' => $this->submitted_at ?? now(),
        ])->saveQuietly();

        // The owner's acknowledgement is recorded automatically on submission.
        $this->recordAcknowledgement(GoalSheetAcknowledgement::PARTY_OWNER, $submitter);

        $this->refresh();
    }
}
