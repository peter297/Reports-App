<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalSheetGoal extends Model
{
    protected $table = 'goal_sheet_goals';

    protected $fillable = [
        'goal_sheet_id', 'position', 'goal_category_id', 'category_name', 'goal_number',
        'goal_statement', 'goal_for_year', 'uom', 'calculation_method', 'source_of_data',
        'achievement_type', 'performance_weight',
    ];

    protected $casts = [
        'position' => 'integer',
        'goal_number' => 'integer',
        'goal_for_year' => 'decimal:2',
        'performance_weight' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::saving(function (GoalSheetGoal $goal): void {
            GoalSheetEntry::guardSheetWritable($goal->sheet);
        });

        static::deleting(function (GoalSheetGoal $goal): void {
            GoalSheetEntry::guardSheetWritable($goal->sheet);
        });
    }

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(GoalSheet::class, 'goal_sheet_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GoalCategory::class, 'goal_category_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(GoalSheetEntry::class, 'goal_sheet_goal_id')->orderBy('position');
    }

    /** AVERAGE() of an entry metric across the entries that have a value (workbook semantics). */
    public function averageFor(string $metric): ?float
    {
        $values = $this->entries
            ->map(fn (GoalSheetEntry $entry) => $entry->{$metric})
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (float) $value);

        return $values->isEmpty() ? null : round($values->avg(), 2);
    }

    /**
     * Goal attainment (%) from recorded checkpoint results vs the termly target
     * for the term the checkpoint belongs to. HB: actual/target, LB: target/actual.
     * Returns null when there is no data.
     */
    public function attainment(): ?float
    {
        $ratios = [];

        foreach ($this->entries as $entry) {
            foreach (GoalSheetEntry::CHECKPOINT_TERM as $checkpoint => $termColumn) {
                $actual = $entry->{$checkpoint};
                $target = $entry->{$termColumn};

                if ($actual === null || $target === null || (float) $target <= 0.0) {
                    continue;
                }

                $actual = (float) $actual;
                $target = (float) $target;

                $ratios[] = $this->isLowerBetter()
                    ? ($actual > 0 ? $target / $actual * 100 : null)
                    : $actual / $target * 100;
            }
        }

        $ratios = array_values(array_filter($ratios, fn ($ratio) => $ratio !== null));

        return $ratios === [] ? null : round(array_sum($ratios) / count($ratios), 2);
    }

    public function isLowerBetter(): bool
    {
        return mb_strtoupper((string) $this->achievement_type) === 'LB';
    }
}
