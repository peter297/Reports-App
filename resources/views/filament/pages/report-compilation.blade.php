<x-filament-panels::page>
    @php
        $rows = $this->getTrackingRowsProperty();
        $submitted = collect($rows)->filter(fn ($row) => $row['report_id'] !== null)->count();
        $missing = collect($rows)->where('is_missing', true)->count();
        $kindLabels = $this->availableKinds();
    @endphp

    <div class="ink-page">
        {{-- Controls --}}
        <div class="ink-card" style="display:flex; flex-wrap:wrap; gap:1rem; align-items:flex-end;">
            <div style="display:grid; gap:0.25rem; min-width:18rem; flex:2 1 18rem;">
                <label class="ink-stat-label" for="kind">Consolidated report</label>
                <select id="kind" class="ink-select" wire:model.live="kind">
                    @foreach ($kindLabels as $label => $value)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:grid; gap:0.25rem;">
                <label class="ink-stat-label" for="periodStart">Period start</label>
                <input id="periodStart" type="date" class="ink-input" wire:model.live="periodStart">
            </div>
            <div style="display:grid; gap:0.25rem;">
                <label class="ink-stat-label" for="periodEnd">Period end</label>
                <input id="periodEnd" type="date" class="ink-input" wire:model.live="periodEnd">
            </div>
            <div style="margin-left:auto; text-align:right;">
                <div class="ink-stat-label">Status</div>
                <div style="font-weight:900; font-size:1.1rem; color:{{ $missing === 0 ? 'var(--ok)' : 'var(--warn)' }};">
                    {{ $submitted }}/{{ count($rows) }} submitted
                </div>
            </div>
        </div>

        @if ($missing > 0)
            <div class="ink-gaps">
                <strong>{{ $missing }} report(s) outstanding.</strong> The draft will flag them explicitly. You can compile now or wait for submissions.
                <label style="display:inline-flex; align-items:center; gap:0.35rem; margin-left:0.5rem; font-weight:600;">
                    <input type="checkbox" wire:model="includeMissing"> Include missing-submission notices in the draft
                </label>
            </div>
        @else
            <div class="ink-card" style="border-left:4px solid var(--ok);">
                <strong style="color:var(--ok);">All expected submissions received.</strong> You can compile the consolidated report now.
            </div>
        @endif

        {{-- Tracking matrix --}}
        <div class="ink-table-wrap">
            <table class="ink-table">
                <thead>
                    <tr>
                        <th style="width:3rem;">Compile</th>
                        <th>Branch</th>
                        <th>Role</th>
                        <th>Submitter</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="{{ $row['is_missing'] ? 'ink-row-gap' : 'ink-row' }}">
                            <td style="text-align:center;">
                                @if ($row['report_id'])
                                    <input type="checkbox" value="{{ $row['report_id'] }}" wire:model="selected" style="width:1rem;height:1rem;accent-color:var(--accent);">
                                @else
                                    <span class="ink-faint">—</span>
                                @endif
                            </td>
                            <td style="font-weight:700;">{{ $row['branch'] ?? 'All branches' }}</td>
                            <td>{{ $row['role'] }}</td>
                            <td>{{ $row['name'] }}</td>
                            <td>
                                @if ($row['report_id'])
                                    <span class="ink-pill ink-pill-ok">{{ $row['status'] }}</span>
                                @elseif ($row['overdue'])
                                    <span class="ink-pill ink-pill-bad">Pending · overdue</span>
                                @else
                                    <span class="ink-pill ink-pill-mute">Pending</span>
                                @endif
                            </td>
                            <td class="ink-faint" style="font-size:0.7rem;">{{ $row['submitted_at'] ?? '—' }}</td>
                            <td>
                                @if ($row['report_id'])
                                    <a href="{{ \App\Filament\Resources\OrganizationalReportResource::getUrl('view', ['record' => $row['report_id']]) }}" style="color:var(--info); font-size:0.75rem; font-weight:700;">Open →</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding:1.5rem; text-align:center; color:var(--ink-faint);">No expected submissions for this period yet. Expected submitters are derived from active reporting assignments.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Compile action --}}
        <div class="ink-card no-print" style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
            <div class="ink-faint" style="font-size:0.75rem;">Select the submitted reports to include, then compile. The draft stays editable before you send it on.</div>
            <button type="button" wire:click="compile" wire:confirm="Create the consolidated draft from the selected reports?" class="ink-btn ink-btn-primary" style="font-size:0.85rem; padding:0.6rem 1.25rem;" @if (count($this->selected) === 0) disabled @endif>Compile consolidated draft →</button>
        </div>

        {{-- Prior compilations --}}
        @if (count($this->getCompilationRowsProperty()))
            <div class="ink-card">
                <h3 class="ink-stat-label" style="margin-top:0;">Your consolidations this period</h3>
                <ul style="margin:0; padding:0; list-style:none; display:grid; gap:0.35rem;">
                    @foreach ($this->getCompilationRowsProperty() as $row)
                        <li style="display:flex; justify-content:space-between; gap:1rem; border-bottom:1px dashed var(--ink-line); padding-bottom:0.3rem; font-size:0.8rem;">
                            <span><a href="{{ \App\Filament\Resources\OrganizationalReportResource::getUrl('view', ['record' => $row['id']]) }}" style="color:var(--info);">{{ $row['title'] }}</a></span>
                            <span class="ink-faint">{{ ucfirst($row['kind']) }} · {{ $row['status'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</x-filament-panels::page>
