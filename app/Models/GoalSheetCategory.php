<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalSheetCategory extends Model
{
    protected $fillable = ['name', 'code', 'description', 'sort_order', 'is_active'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function goalSheets(): HasMany
    {
        return $this->hasMany(GoalSheet::class);
    }

    /**
     * Best-effort mapping of a goal sheet category to an academic level name,
     * used for reporting breakdowns. Not used for authorization.
     */
    public function academicLevelName(): ?string
    {
        $name = mb_strtoupper($this->name);

        return match (true) {
            str_contains($name, 'E.Y.E') || str_contains($name, 'EYE') => 'Early Years Education',
            str_contains($name, 'UPPER PRIMARY') => 'Upper Primary',
            str_contains($name, 'JUNIOR SCHOOL') || str_contains($name, 'JUNIOR') => 'Junior School',
            default => null,
        };
    }
}
