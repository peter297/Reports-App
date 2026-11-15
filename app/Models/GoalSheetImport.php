<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalSheetImport extends Model
{
    protected $fillable = ['filename', 'uploaded_by', 'status', 'summary', 'errors'];

    protected $casts = [
        'summary' => 'array',
        'errors' => 'array',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
