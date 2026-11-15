<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalMeasure extends Model
{
    protected $fillable = [
        'goal_category_id', 'name', 'goal_statement', 'uom', 'calculation_method', 'source_of_data',
        'achievement_type', 'performance_weight', 'goal_for_year', 'description', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'performance_weight' => 'decimal:4',
        'goal_for_year' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(GoalCategory::class, 'goal_category_id');
    }
}
