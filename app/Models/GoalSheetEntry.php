<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalSheetEntry extends Model
{
    protected $fillable = [
        'goal_sheet_goal_id', 'position', 'subject_name', 'class_name',
        'term_1', 'term_2', 'term_3',
        'op_t1', 'mid_t1', 'end_t1', 'mid_t2', 'end_t2', 'mid_t3', 'end_t3',
    ];

    protected $casts = [
        'position' => 'integer',
        'term_1' => 'decimal:2',
        'term_2' => 'decimal:2',
        'term_3' => 'decimal:2',
        'op_t1' => 'decimal:2',
        'mid_t1' => 'decimal:2',
        'end_t1' => 'decimal:2',
        'mid_t2' => 'decimal:2',
        'end_t2' => 'decimal:2',
        'mid_t3' => 'decimal:2',
        'end_t3' => 'decimal:2',
    ];

    public const CHECKPOINTS = ['op_t1', 'mid_t1', 'end_t1', 'mid_t2', 'end_t2', 'mid_t3', 'end_t3'];

    /** Checkpoint => termly target column that applies to it. */
    public const CHECKPOINT_TERM = [
        'op_t1' => 'term_1',
        'mid_t1' => 'term_1',
        'end_t1' => 'term_1',
        'mid_t2' => 'term_2',
        'end_t2' => 'term_2',
        'mid_t3' => 'term_3',
        'end_t3' => 'term_3',
    ];

    protected static function booted(): void
    {
        // A goal sheet's entries may only be written while the sheet is a draft
        // (super admin may intervene). Enforced at model level, not just in the UI.
        static::saving(function (GoalSheetEntry $entry): void {
            $sheet = $entry->goal?->sheet;
            self::guardSheetWritable($sheet);
        });

        static::deleting(function (GoalSheetEntry $entry): void {
            $sheet = $entry->goal?->sheet;
            self::guardSheetWritable($sheet);
        });
    }

    public static function guardSheetWritable(?GoalSheet $sheet): void
    {
        $user = \Illuminate\Support\Facades\Auth::hasUser()
            ? \Illuminate\Support\Facades\Auth::user()
            : null;

        if ($sheet === null) {
            return;
        }

        if ($user?->isSuperAdmin()) {
            return;
        }

        abort_unless(
            $sheet->status === GoalSheet::STATUS_DRAFT,
            403,
            'This goal sheet has been submitted and can no longer be edited.'
        );
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(GoalSheetGoal::class, 'goal_sheet_goal_id');
    }

    /** Which checkpoints have a recorded value. */
    public function hasCheckpoint(string $checkpoint): bool
    {
        return $this->{$checkpoint} !== null;
    }
}
