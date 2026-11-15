<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSectionResponse extends Model
{
    protected $fillable = [
        'report_id',
        'section_id',
        'title',
        'sort_order',
        'is_mandatory',
        'data_source',
        'content',
        'auto_payload',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_mandatory' => 'boolean',
            'auto_payload' => 'array',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ReportTemplateSection::class, 'section_id');
    }
}
