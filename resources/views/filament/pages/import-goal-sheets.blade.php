<x-filament-panels::page>
    @php
        $last = $this->lastImport;
        $history = $this->history;
        $tdStyle = 'padding: 0.4rem 0.6rem; border-bottom: 1px solid var(--ink-line); color: var(--ink-text); font-size: 0.73rem;';
        $thStyle = 'padding: 0.45rem 0.6rem; background: var(--row-strand); color:#f9fafb; font-size: 0.62rem; font-weight:800; letter-spacing:0.07em; text-transform: uppercase; text-align:left;';
    @endphp

    <div class="ink-page">

        {{-- Upload --}}
        <div class="ink-card">
            <h2 class="ink-stat-label" style="margin-bottom:0.35rem; font-size:0.78rem;">Import the 2026 GOAL SHEETS workbook</h2>
            <p style="font-size:0.78rem; color:var(--ink-faint); margin:0 0 0.6rem;">
                Reads all six worksheets (CBE E.Y.E, ICDC&nbsp;E.Y.E, CBE/ICDC UPPER PRIMARY, C.B.E/I.C.D.C JUNIOR SCHOOL),
                identifies each staff goal sheet, and imports staff details, goals, subjects, classes, termly targets and
                monthly checkpoints. Percentages are normalized (fractions such as 0.8 become 80%; recorded actuals such as
                84.49 stay as-is). Re-importing the same workbook updates the existing sheets in place — no duplicates.
            </p>
            <form wire:submit="import" style="display:flex; gap:1rem; align-items:flex-end; flex-wrap:wrap;">
                <label style="flex:1 1 320px;">
                    <span class="ink-stat-label">Excel workbook (.xlsx)</span>
                    <input type="file" wire:model="workbook" accept=".xlsx" class="ink-input" style="width:100%; padding:0.35rem;">
                    @error('workbook') <span style="color:var(--bad); font-size:0.72rem;">{{ $message }}</span> @enderror
                    @if ($workbook)
                        <span style="font-size:0.7rem; color:var(--ok);">Ready to upload: {{ $workbook->getClientOriginalName() }} ({{ number_format($workbook->getSize() / 1024, 1) }} KB)</span>
                    @endif
                </label>
                <label style="display:flex; gap:0.5rem; align-items:center; padding-bottom:0.35rem;">
                    <input type="checkbox" wire:model="dryRun" style="width:1.1rem; height:1.1rem; accent-color: var(--accent);">
                    <span style="font-size:0.78rem; color:var(--ink-text); font-weight:700;">Dry run (validate only, write nothing)</span>
                </label>
                <button type="submit" class="ink-btn {{ $dryRun ? 'ink-btn-info' : 'ink-btn-solid' }}" wire:loading.attr="disabled">
                    {{ $dryRun ? 'Validate workbook' : 'Import workbook' }}
                </button>
            </form>
            <div class="ink-bar" style="margin-top:0.6rem;" wire:loading wire:target="import"><div class="ink-bar-fill ink-bar-fill-info" style="width:60%"></div></div>
        </div>

        {{-- Last import report --}}
        @if ($last)
            @php
                $summary = $last->summary ?? [];
                $sheets = $summary['sheets'] ?? [];
                $errors = $last->errors ?? [];
            @endphp
            <div class="ink-card ink-card-flush">
                <div style="padding:0.6rem 0.9rem; background:var(--row-strand); border-bottom:2px solid var(--accent); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                    <div>
                        <span class="ink-stat-label" style="color:#f9fafb;">Latest import</span>
                        <div style="font-size:0.82rem; color:#fbbf24; font-weight:700; margin-top:0.15rem;">{{ $last->filename }} · {{ $last->created_at?->format('d M Y, H:i') }} · {{ $last->uploadedBy?->name }}</div>
                    </div>
                    <span class="ink-pill {{ $last->status === 'completed' ? 'ink-pill-ok' : ($last->status === 'dry_run' ? 'ink-pill-info' : 'ink-pill-bad') }}">{{ strtoupper(str_replace('_', ' ', $last->status)) }}</span>
                </div>
                <div style="padding:0.8rem 0.9rem;">
                    <div class="ink-stats ink-stats-6" style="margin-bottom:0.75rem;">
                        <div class="ink-stat ink-stat-ok"><div class="ink-stat-label">Created</div><div class="ink-stat-value">{{ $summary['created'] ?? 0 }}</div></div>
                        <div class="ink-stat ink-stat-info"><div class="ink-stat-label">Updated</div><div class="ink-stat-value">{{ $summary['updated'] ?? 0 }}</div></div>
                        <div class="ink-stat ink-stat-bad"><div class="ink-stat-label">Failed</div><div class="ink-stat-value">{{ $summary['failed'] ?? 0 }}</div></div>
                        <div class="ink-stat ink-stat-total"><div class="ink-stat-label">Goals</div><div class="ink-stat-value">{{ $summary['goals'] ?? 0 }}</div></div>
                        <div class="ink-stat ink-stat-total"><div class="ink-stat-label">Subject Rows</div><div class="ink-stat-value">{{ $summary['entries'] ?? 0 }}</div></div>
                        <div class="ink-stat ink-stat-warn"><div class="ink-stat-label">Warnings</div><div class="ink-stat-value">{{ $summary['warnings'] ?? 0 }}</div></div>
                    </div>

                    @if (count($sheets) > 0)
                        <div class="ink-table-wrap ink-table-sticky-first" style="overflow-x:auto; max-height:340px;">
                            <table class="ink-table" style="min-width:760px;">
                                <thead>
                                    <tr><th style="{{ $thStyle }}">Worksheet</th><th style="{{ $thStyle }}">Staff Member</th><th style="{{ $thStyle }}">Account</th><th style="{{ $thStyle }}">Result</th><th style="{{ $thStyle }}; text-align:center;">Goals</th><th style="{{ $thStyle }}; text-align:center;">Rows</th><th style="{{ $thStyle }}">Warnings</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($sheets as $sheet)
                                        <tr class="ink-row">
                                            <td style="{{ $tdStyle }}; font-size:0.68rem;">{{ $sheet['worksheet'] }}</td>
                                            <td style="{{ $tdStyle }}; font-weight:700;">{{ $sheet['staff'] }}</td>
                                            <td style="{{ $tdStyle }};">
                                                @if ($sheet['staff_linked'])
                                                    <span class="ink-pill ink-pill-ok">Linked</span>
                                                @else
                                                    <span class="ink-pill ink-pill-warn">Unlinked</span>
                                                @endif
                                            </td>
                                            <td style="{{ $tdStyle }};"><span class="ink-pill {{ $sheet['status'] === 'created' ? 'ink-pill-ok' : 'ink-pill-info' }}">{{ strtoupper($sheet['status']) }}</span></td>
                                            <td style="{{ $tdStyle }}; text-align:center;">{{ $sheet['goals'] }}</td>
                                            <td style="{{ $tdStyle }}; text-align:center;">{{ $sheet['entries'] }}</td>
                                            <td style="{{ $tdStyle }}; color:var(--warn); font-size:0.68rem;">{{ ($sheet['warnings'] ?? []) === [] ? '' : count($sheet['warnings']) . ' warning(s)' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if (count($errors) > 0)
                        <details style="margin-top:0.6rem; border:1px solid var(--bad-soft); border-radius:0.5rem; padding:0.5rem 0.75rem; background:rgba(185,28,28,0.05);">
                            <summary style="cursor:pointer; font-weight:800; font-size:0.75rem; color:var(--bad);">Errors &amp; warnings ({{ count($errors) }})</summary>
                            <ul style="margin:0.5rem 0 0.25rem 1.1rem; font-size:0.72rem; color:var(--ink-text); line-height:1.6;">
                                @foreach ($errors as $error)
                                    <li style="{{ ($error['warning'] ?? false) ? 'color:var(--warn);' : 'color:var(--bad);' }}">
                                        <strong>{{ $error['worksheet'] ?? '?' }}@if(!empty($error['row'])), row {{ $error['row'] }}@endif:</strong> {{ $error['message'] }}
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                </div>
            </div>
        @endif

        {{-- Import history --}}
        <div class="ink-card ink-card-flush">
            <h3 class="ink-stat-label" style="padding:0.6rem 0.9rem; background:var(--row-strand); color:#f9fafb; border-bottom:2px solid var(--accent); font-size:0.68rem;">Recent imports</h3>
            @if ($history->isEmpty())
                <div style="padding:1.1rem; text-align:center; color:var(--ink-faint); font-size:0.78rem;">Nothing imported yet.</div>
            @else
                <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                <table style="width:100%; border-collapse:collapse; min-width:600px;">
                    <tbody>
                        @foreach ($history as $entry)
                            <tr class="ink-row">
                                <td style="{{ $tdStyle }}; font-size:0.7rem; white-space:nowrap;">{{ $entry->created_at?->format('d M Y H:i') }}</td>
                                <td style="{{ $tdStyle }}; font-weight:700; max-width:280px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $entry->filename }}</td>
                                <td style="{{ $tdStyle }}; color:var(--ink-faint);">{{ $entry->uploadedBy?->name }}</td>
                                <td style="{{ $tdStyle }};">
                                    <span class="ink-pill {{ $entry->status === 'completed' ? 'ink-pill-ok' : ($entry->status === 'dry_run' ? 'ink-pill-info' : 'ink-pill-bad') }}">{{ strtoupper(str_replace('_', ' ', $entry->status)) }}</span>
                                </td>
                                <td style="{{ $tdStyle }}; text-align:right; font-size:0.7rem;">
                                    created {{ $entry->summary['created'] ?? 0 }} · updated {{ $entry->summary['updated'] ?? 0 }} · failed {{ $entry->summary['failed'] ?? 0 }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
