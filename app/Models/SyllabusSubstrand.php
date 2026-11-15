<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyllabusSubstrand extends Model
{
    use HasFactory;

    protected $fillable = [
        'syllabus_strand_id',
        'name',
        'description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function strand(): BelongsTo
    {
        return $this->belongsTo(SyllabusStrand::class, 'syllabus_strand_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(SyllabusLesson::class)->orderBy('sort_order')->orderBy('lesson_number')->orderBy('id');
    }
}
