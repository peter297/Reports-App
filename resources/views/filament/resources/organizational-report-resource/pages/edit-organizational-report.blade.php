<x-filament-panels::page>
    @php
        $labels = $this->dataSourceLabels;
        $status = $this->record->status;
        $statusColor = match ($status) {
            \App\Enums\ReportStatus::Draft => 'var(--ink-faint)',
            \App\Enums\ReportStatus::Submitted, \App\Enums\ReportStatus::Received => 'var(--info)',
            \App\Enums\ReportStatus::UnderReview, \App\Enums\ReportStatus::Resubmitted => 'var(--warn)',
            \App\Enums\ReportStatus::Returned => 'var(--bad)',
            \App\Enums\ReportStatus::Approved => 'var(--ok)',
            default => 'var(--ink-faint)',
        };
    @endphp

    <div class="ink-page">
        {{-- Header --}}
        <div class="ink-card" style="display:flex; flex-wrap:wrap; gap:1.5rem; align-items:flex-start; justify-content:space-between;">
            <div>
                <h2 style="margin:0; font-size:1.1rem; font-weight:900; color:var(--ink-text);">{{ $this->record->title }}</h2>
                <p class="ink-faint" style="margin:0.2rem 0 0; font-size:0.75rem;">
                    Submitted by {{ $this->record->user?->name }} ({{ $this->record->role?->name ?? 'no role' }}) ·
                    {{ $this->record->branch?->name ?? 'All branches' }} ·
                    Period {{ $this->record->period_start?->format('d/m/Y') }} – {{ $this->record->period_end?->format('d/m/Y') }}
                    @if ($this->record->deadline) · Deadline {{ $this->record->deadline->format('d/m/Y') }} @endif
                </p>
                <span style="display:inline-block; margin-top:0.5rem; padding:0.15rem 0.65rem; border-radius:9999px; border:1px solid {{ $statusColor }}; color:{{ $statusColor }}; font-size:0.65rem; font-weight:800; letter-spacing:0.06em; text-transform:uppercase;">{{ $status->label() }}</span>
            </div>
            <a href="{{ \App\Filament\Resources\OrganizationalReportResource::getUrl('index') }}" class="ink-btn ink-btn-ghost">← Back to reports</a>
        </div>

        @if ($status === \App\Enums\ReportStatus::Returned)
            @php
                $latestReturn = $this->record->reviews->where('action', 'returned')->sortByDesc('created_at')->first();
            @endphp
            <div class="ink-gaps">
                <strong>This report was returned for revision.</strong>
                @if ($latestReturn?->comment) <br>“{{ $latestReturn->comment }}” @endif
                <br><span style="font-weight:400;">Make the requested changes below, then resubmit.</span>
            </div>
        @endif

        {{-- Refresh bar --}}
        <div class="ink-card no-print" style="display:flex; justify-content:space-between; align-items:center; gap:0.75rem; flex-wrap:wrap;">
            <span class="ink-faint" style="font-size:0.75rem;">Sections labelled <strong>Auto</strong> are filled from live system data — refresh them before submitting.</span>
            <button type="button" wire:click="refreshAllAutoSections" class="ink-btn ink-btn-info">Refresh all automatic sections</button>
        </div>

        {{-- Responses --}}
        @foreach ($this->responses as $index => $response)
            <div class="ink-card" wire:key="response-{{ $response['id'] }}">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:0.75rem; flex-wrap:wrap; margin-bottom:0.35rem;">
                    <h3 style="margin:0; font-size:0.9rem; font-weight:800; color:var(--ink-text);">
                        {{ $response['title'] }}
                        @if ($response['is_mandatory'])<span style="color:var(--bad);" title="Mandatory"> *</span>@endif
                        @if ($response['is_auto'])<span class="ink-pill ink-pill-info" style="margin-left:0.4rem;">Auto · {{ $labels[$response['data_source']] ?? $response['data_source'] }}</span>@endif
                    </h3>
                    @if ($response['is_auto'])
                        <button type="button" wire:click="refreshAutoSection({{ $response['id'] }})" class="ink-btn ink-btn-ghost" style="padding:0.3rem 0.6rem; font-size:0.65rem;">Refresh from system</button>
                    @endif
                </div>
                <textarea
                    class="ink-textarea"
                    style="width:100%; min-height:5.5rem; font-family:inherit;"
                    rows="4"
                    wire:model.blur="responses.{{ $index }}.content"
                    @if ($response['is_mandatory']) required @endif
                    placeholder="{{ $response['is_auto'] ? 'Filled automatically — or write your own observation.' : 'Write your narrative…' }}"
                ></textarea>
            </div>
        @endforeach

        {{-- Attachments --}}
        <div class="ink-card">
            <h3 class="ink-stat-label" style="margin-top:0;">Attachments</h3>
            @if ($this->record->attachments->isNotEmpty())
                <ul style="margin:0.3rem 0 0; padding:0; list-style:none; display:grid; gap:0.3rem;">
                    @foreach ($this->record->attachments as $attachment)
                        <li style="display:flex; justify-content:space-between; align-items:center; gap:0.75rem; border-bottom:1px dashed var(--ink-line); padding-bottom:0.3rem;">
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->file_path) }}" target="_blank" style="color:var(--info); font-size:0.8rem;">{{ $attachment->original_name ?? basename($attachment->file_path) }}</a>
                            <button type="button" wire:click="deleteAttachment({{ $attachment->id }})" style="font-size:0.65rem; color:var(--bad); background:none; border:none; cursor:pointer;">Remove</button>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="ink-faint" style="margin:0.3rem 0 0; font-size:0.8rem;">No attachments yet.</p>
            @endif
            <div style="display:flex; gap:0.5rem; align-items:center; margin-top:0.6rem; flex-wrap:wrap;">
                <input type="file" wire:model="uploads" multiple class="ink-input" style="max-width:24rem;">
                <button type="button" wire:click="uploadAttachment" class="ink-btn ink-btn-solid" style="padding:0.4rem 0.7rem;">Upload</button>
                <span class="ink-faint" style="font-size:0.65rem;">PDF, Word, Excel, images or text · up to 10 MB each</span>
            </div>
            <div wire:loading wire:target="uploads" class="ink-faint" style="font-size:0.7rem; margin-top:0.25rem;">Uploading…</div>
        </div>

        {{-- Footer actions --}}
        <div class="ink-card no-print" style="display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; flex-wrap:wrap;">
            <div style="display:grid; gap:0.25rem; max-width:32rem;">
                <label class="ink-stat-label" for="note">Covering note (optional)</label>
                <textarea id="note" class="ink-textarea" rows="2" wire:model="note" placeholder="Anything the reviewer should know…"></textarea>
            </div>
            <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
                <button type="button" wire:click="saveDraft" class="ink-btn ink-btn-solid">Save draft</button>
                <button type="button" wire:click="submitReport" wire:confirm="Submit this report to your line manager?" class="ink-btn ink-btn-primary" style="font-size:0.85rem; padding:0.6rem 1.25rem;">Submit report →</button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
