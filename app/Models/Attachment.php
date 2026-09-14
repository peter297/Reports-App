<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $fillable = [
        'report_id', 
        'file_path',
    ];


    protected $casts = [
        'file_path' => 'array', // Automatically cast the file_paths column to an array
    ];

    // Relationships

    /**
     * The report this attachment belongs to.
     */
    

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
