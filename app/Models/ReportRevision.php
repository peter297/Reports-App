<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportRevision extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'revision_number',
        'snapshot',
        'note',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
