<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoalSheetAcknowledgement extends Model
{
    public const PARTY_OWNER = 'owner';
    public const PARTY_LINE_MANAGER_1 = 'line_manager_1';
    public const PARTY_LINE_MANAGER_2 = 'line_manager_2';

    public const PARTIES = [
        self::PARTY_OWNER => 'Goal Sheet Owner (Staff Member)',
        self::PARTY_LINE_MANAGER_1 => 'Line Manager 1 (Coordinator)',
        self::PARTY_LINE_MANAGER_2 => 'Line Manager 2 (Deputy)',
    ];

    protected $fillable = [
        'goal_sheet_id', 'party', 'signer_user_id', 'signer_name',
        'signed', 'signature_path', 'signed_at', 'notes',
    ];

    protected $casts = [
        'signed' => 'boolean',
        'signed_at' => 'datetime',
    ];

    public function sheet(): BelongsTo
    {
        return $this->belongsTo(GoalSheet::class, 'goal_sheet_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_user_id');
    }
}
