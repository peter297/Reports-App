<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Received = 'received';
    case UnderReview = 'under_review';
    case Returned = 'returned';
    case Resubmitted = 'resubmitted';
    case Approved = 'approved';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Received => 'Received',
            self::UnderReview => 'Under Review',
            self::Returned => 'Returned for Revision',
            self::Resubmitted => 'Resubmitted',
            self::Approved => 'Approved',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'info',
            self::Received => 'info',
            self::UnderReview => 'warning',
            self::Returned => 'danger',
            self::Resubmitted => 'warning',
            self::Approved => 'success',
            self::Archived => 'gray',
        };
    }

    /** Statuses visible to recipients in their inbox (i.e. submitted work awaiting action). */
    public function isPendingReview(): bool
    {
        return in_array($this, [self::Submitted, self::Resubmitted, self::Received, self::UnderReview], true);
    }

    /** Statuses that count as "not yet submitted" for overdue tracking. */
    public function isUnsubmitted(): bool
    {
        return in_array($this, [self::Draft, self::Returned], true);
    }
}
