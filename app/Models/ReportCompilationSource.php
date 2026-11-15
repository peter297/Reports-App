<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCompilationSource extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'compilation_id',
        'source_report_id',
        'branch_label',
        'contribution',
    ];

    protected function casts(): array
    {
        return [
            'contribution' => 'array',
        ];
    }

    public function compilation(): BelongsTo
    {
        return $this->belongsTo(ReportCompilation::class, 'compilation_id');
    }

    public function sourceReport(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'source_report_id');
    }
}
