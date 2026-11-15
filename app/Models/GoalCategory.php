<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoalCategory extends Model
{
    protected $fillable = ['name', 'code', 'description', 'sort_order', 'is_active'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function measures(): HasMany
    {
        return $this->hasMany(GoalMeasure::class)->orderBy('sort_order');
    }

    public function goals(): HasMany
    {
        return $this->hasMany(GoalSheetGoal::class);
    }
}
