<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyllabusCoordinatorReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'syllabus_strand_id',
        'coordinator_id',
        'comment',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function strand(): BelongsTo
    {
        return $this->belongsTo(SyllabusStrand::class, 'syllabus_strand_id');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }
}
