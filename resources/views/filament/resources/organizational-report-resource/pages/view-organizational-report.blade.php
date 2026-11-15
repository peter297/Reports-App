<x-filament-panels::page>
    @php
        $record = $this->record;
        $status = $record->status;
        $statusColor = match ($status) {
            \App\Enums\ReportStatus::Draft => 'var(--ink-faint)',
            \App\Enums\ReportStatus::Submitted, \App\Enums\ReportStatus::Received => 'var(--info)',
            \App\Enums\ReportStatus::UnderReview, \App\Enums\ReportStatus::Resubmitted => 'var(--warn)',
            \App\Enums\ReportStatus::Returned => 'var(--bad)',
            \App\Enums\ReportStatus::Approved => 'var(--ok)',
            default => 'var(--ink-faint)',
        };
        $isRecipient = $this->isRecipientProperty();
        $canReview = $this->canReviewProperty();
    @endphp

    <div class="ink-page">
        {{-- Header --}}
        <div class="ink-card" style="display:flex; flex-wrap:wrap; gap:1.5rem; align-items:flex-start; justify-content:space-between;">
            <div>
                <h2 style="margin:0; font-size:1.1rem; font-weight:900; color:var(--ink-text);">{{ $record->title }}</h2>
                <p class="ink-faint" style="margin:0.25rem 0 0; font-size:0.75rem;">
                    Submitted by <strong>{{ $record->user?->name }}</strong> ({{ $record->role?->name ?? 'no role' }}) ·
                    {{ $record->branch?->name ?? 'All branches' }}{{ $record->academicLevel ? ' · ' . $record->academicLevel->name : '' }} ·
                    Period {{ $record->period_start?->format('d/m/Y') }} – {{ $record->period_end?->format('d/m/Y') }}
                    @if ($record->deadline) · Deadline {{ $record->deadline->format('d/m/Y') }} @endif
                    · Revision {{ $record->current_revision }}
                    @if ($record->is_consolidated) · <strong>Consolidated {{ str_replace('_', ' ', (string) $record->compilation_kind) }} report</strong>@endif
                </p>
                <span style="display:inline-block; margin-top:0.5rem; padding:0.15rem 0.65rem; border-radius:9999px; border:1px solid {{ $statusColor }}; color:{{ $statusColor }}; font-size:0.65rem; font-weight:800; letter-spacing:0.06em; text-transform:uppercase;">{{ $status->label() }}</span>
                @if ($record->submitted_at)
                    <span class="ink-faint" style="font-size:0.7rem; margin-left:0.4rem;">Submitted {{ $record->submitted_at->format('d/m/Y H:i') }}</span>
                @endif
            </div>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <a href="{{ \App\Filament\Resources\OrganizationalReportResource::getUrl('index') }}" class="ink-btn ink-btn-ghost">← Back</a>
                @can('reports.create')
                    @if ($record->isEditableBy(auth()->user()))
                        <a href="{{ \App\Filament\Resources\OrganizationalReportResource::getUrl('edit', ['record' => $record->id]) }}" class="ink-btn ink-btn-primary">Continue editing →</a>
                    @endif
                @endcan
            </div>
        </div>

        {{-- Recipient review actions --}}
        @if ($canReview && ! in_array($status, [\App\Enums\ReportStatus::Draft, \App\Enums\ReportStatus::Archived], true))
            <div class="ink-card no-print">
                <h3 class="ink-stat-label" style="margin-top:0;">Review actions</h3>
                <div style="display:grid; gap:0.5rem;">
                    <textarea rows="2" class="ink-textarea" wire:model="reviewComment" placeholder="Comment for the submitter (required when returning)…"></textarea>
                    @error('reviewComment')<p style="font-size:0.65rem; color:var(--bad);">{{ $message }}</p>@enderror
                    <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                        @can('reports.review')
                            @if (in_array($status, [\App\Enums\ReportStatus::Submitted, \App\Enums\ReportStatus::Resubmitted], true))
                                <button type="button" wire:click="markReceived" class="ink-btn ink-btn-solid">Mark received</button>
                            @endif
                            @if (in_array($status, [\App\Enums\ReportStatus::Submitted, \App\Enums\ReportStatus::Resubmitted, \App\Enums\ReportStatus::Received], true))
                                <button type="button" wire:click="markUnderReview" class="ink-btn ink-btn-info">Under review</button>
                            @endif
                            <button type="button" wire:click="archiveReport" class="ink-btn ink-btn-ghost" wire:confirm="Archive this report?">Archive</button>
                        @endcan
                        @can('reports.return')
                            <button type="button" wire:click="returnForRevision" class="ink-btn ink-btn-danger" wire:confirm="Return this report to the submitter for revision?">Return for revision</button>
                        @endcan
                        @can('reports.approve')
                            @if (! in_array($status, [\App\Enums\ReportStatus::Approved], true))
                                <button type="button" wire:click="approveReport" class="ink-btn ink-btn-success">Approve</button>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>
        @endif

        {{-- Consolidated sources --}}
        @if ($record->compilation && $record->compilation->sources->isNotEmpty())
            <div class="ink-card">
                <h3 class="ink-stat-label" style="margin-top:0; color:var(--accent);">Compiled from {{ $record->compilation->sources->count() }} source report(s)</h3>
                <ul style="margin:0; padding:0; list-style:none; display:grid; gap:0.3rem;">
                    @foreach ($record->compilation->sources as $src)
                        <li style="display:flex; justify-content:space-between; gap:0.75rem; border-bottom:1px dashed var(--ink-line); padding-bottom:0.3rem; font-size:0.8rem;">
                            <span><strong>{{ $src->branch_label ?? 'School-wide' }}</strong> — {{ $src->sourceReport?->title ?? ('Report #' . $src->source_report_id) }}</span>
                            <a href="{{ \App\Filament\Resources\OrganizationalReportResource::getUrl('view', ['record' => $src->source_report_id]) }}" style="color:var(--info);">Open →</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Responses --}}
        @foreach ($record->responses as $response)
            <div class="ink-card">
                <h3 style="margin:0 0 0.35rem; font-size:0.9rem; font-weight:800; color:var(--ink-text);">{{ $response->title }}</h3>
                @if (trim((string) $response->content) !== '')
                    <div style="font-size:0.85rem; line-height:1.5; color:var(--ink-text); overflow-x:auto;">{!! $response->content !!}</div>
                @else
                    <p class="ink-faint" style="font-size:0.8rem;">— not provided —</p>
                @endif
            </div>
        @endforeach

        <div class="ink-grid-2">
            {{-- Review history --}}
            <div class="ink-card">
                <h3 class="ink-stat-label" style="margin-top:0;">Review history</h3>
                @if ($record->reviews->isEmpty())
                    <p class="ink-faint" style="font-size:0.8rem;">No activity yet.</p>
                @else
                    <ul style="margin:0; padding:0; list-style:none; display:grid; gap:0.4rem; max-height:16rem; overflow:auto;">
                        @foreach ($record->reviews as $review)
                            <li style="border-left:3px solid var(--ink-line); padding:0.25rem 0.6rem;">
                                <div style="display:flex; justify-content:space-between; gap:0.5rem; font-size:0.75rem;">
                                    <strong>{{ str_replace('_', ' ', $review->action) }}</strong>
                                    <span class="ink-faint">{{ $review->created_at?->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="ink-faint" style="font-size:0.7rem;">{{ $review->user?->name ?? 'System' }}@if ($review->comment) · “{{ $review->comment }}”@endif</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- Attachments --}}
            <div class="ink-card">
                <h3 class="ink-stat-label" style="margin-top:0;">Attachments</h3>
                @if ($record->attachments->isEmpty())
                    <p class="ink-faint" style="font-size:0.8rem;">No attachments.</p>
                @else
                    <ul style="margin:0; padding:0; list-style:none; display:grid; gap:0.3rem;">
                        @foreach ($record->attachments as $attachment)
                            <li>
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->file_path) }}" target="_blank" style="color:var(--info); font-size:0.8rem;">{{ $attachment->original_name ?? basename($attachment->file_path) }}</a>
                                @if ($attachment->size_bytes)<span class="ink-faint" style="font-size:0.65rem;"> · {{ number_format($attachment->size_bytes / 1024, 0) }} KB</span>@endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Comments --}}
        <div class="ink-card no-print">
            <h3 class="ink-stat-label" style="margin-top:0;">Comments</h3>
            @if ($record->comments->isNotEmpty())
                <ul style="margin:0 0 0.6rem; padding:0; list-style:none; display:grid; gap:0.35rem;">
                    @foreach ($record->comments as $comment)
                        <li style="border-bottom:1px dashed var(--ink-line); padding-bottom:0.35rem;">
                            <div style="display:flex; justify-content:space-between; gap:0.5rem; font-size:0.75rem;">
                                <strong>{{ $comment->user?->name ?? 'User' }}</strong>
                                <span class="ink-faint">{{ $comment->created_at?->format('d/m/Y H:i') }}</span>
                            </div>
                            <p style="margin:0.15rem 0 0; font-size:0.8rem;">{{ $comment->comment_text }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
            <div style="display:flex; gap:0.5rem; align-items:flex-start;">
                <textarea class="ink-textarea" style="flex:1;" rows="2" wire:model="comment" placeholder="Add a comment…"></textarea>
                <button type="button" wire:click="addComment" class="ink-btn ink-btn-solid">Post</button>
            </div>
            @error('comment')<p style="font-size:0.65rem; color:var(--bad);">{{ $message }}</p>@enderror
        </div>
    </div>
</x-filament-panels::page>
