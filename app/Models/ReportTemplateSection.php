<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplateSection extends Model
{
    protected $fillable = [
        'report_template_version_id',
        'title',
        'sort_order',
        'is_mandatory',
        'is_auto',
        'data_source',
        'help_text',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_mandatory' => 'boolean',
            'is_auto' => 'boolean',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ReportTemplateVersion::class, 'report_template_version_id');
    }
}
