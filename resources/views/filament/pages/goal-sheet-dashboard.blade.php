<x-filament-panels::page>
    @php
        $stats = $this->statsView;
        $byCategory = $this->byCategory;
        $byCoordinator = $this->byCoordinator;
        $byDepartment = $this->byDepartment;
        $rows = $this->sheetRows;

        $tdStyle = 'padding: 0.45rem 0.7rem; border-bottom: 1px solid var(--ink-line); color: var(--ink-text); font-size: 0.74rem;';
        $thStyle = 'padding: 0.5rem 0.7rem; background: var(--row-strand); color:#f9fafb; font-size: 0.62rem; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; text-align: left;';
        $maxRating = max(1.0, max(array_map(fn ($r) => (float) ($r['avg_rating'] ?? 0), $byCategory->all()) ?: [1.0]));
        $user = auth()->user();
    @endphp

    <div class="ink-page">

        {{-- Filters --}}
        <div class="ink-card no-print ink-filter-bar">
            <label class="ink-filter-item" for="filter-year">
                <span class="ink-stat-label">Goal Year</span>
                <select id="filter-year" wire:model.live="goalYear" class="ink-select">
                    <option value="">All years</option>
                    @foreach ($this->yearOptions as $year => $label)
                        <option value="{{ $year }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="ink-filter-item" for="filter-category">
                <span class="ink-stat-label">Category</span>
                <select id="filter-category" wire:model.live="categoryId" class="ink-select">
                    <option value="">All categories</option>
                    @foreach ($this->categoryOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="ink-filter-item" for="filter-coordinator">
                <span class="ink-stat-label">Coordinator</span>
                <select id="filter-coordinator" wire:model.live="coordinator" class="ink-select">
                    <option value="">All coordinators</option>
                    @foreach ($this->coordinatorOptions as $name => $label)
                        <option value="{{ $name }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="ink-filter-item" for="filter-department">
                <span class="ink-stat-label">Department</span>
                <select id="filter-department" wire:model.live="department" class="ink-select">
                    <option value="">All departments</option>
                    @foreach ($this->departmentOptions as $name => $label)
                        <option value="{{ $name }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        {{-- Summary cards --}}
        <div class="ink-stats ink-stats-6">
            <div class="ink-stat ink-stat-total">
                <div class="ink-stat-label">Goal Sheets</div>
                <div class="ink-stat-value">{{ $stats['total'] }}</div>
            </div>
            <div class="ink-stat ink-stat-pending">
                <div class="ink-stat-label">Draft</div>
                <div class="ink-stat-value">{{ $stats['draft'] }}</div>
            </div>
            <div class="ink-stat ink-stat-warn">
                <div class="ink-stat-label">Submitted</div>
                <div class="ink-stat-value">{{ $stats['submitted'] }}</div>
            </div>
            <div class="ink-stat ink-stat-ok">
                <div class="ink-stat-label">Acknowledged</div>
                <div class="ink-stat-value">{{ $stats['acknowledged'] }}</div>
            </div>
            <div class="ink-stat ink-stat-bad">
                <div class="ink-stat-label">Pending Acknowledgement</div>
                <div class="ink-stat-value">{{ $stats['pending_ack'] }}</div>
            </div>
            <div class="ink-stat ink-stat-cover">
                <div class="ink-stat-label">Avg Weighted Rating</div>
                <div class="ink-stat-value">{{ $stats['avg_rating'] === null ? '—' : number_format($stats['avg_rating'], 1) . '%' }}</div>
            </div>
        </div>

        <div class="ink-grid-2">
            {{-- By category --}}
            <div class="ink-card ink-card-flush">
                <h3 class="ink-stat-label" style="padding:0.6rem 0.9rem; background:var(--row-strand); color:#f9fafb; border-bottom:2px solid var(--accent); font-size:0.68rem;">By workbook category</h3>
                @if ($byCategory->isEmpty())
                    <div style="padding:1.2rem; text-align:center; color:var(--ink-faint); font-size:0.78rem;">No goal sheets for these filters yet.</div>
                @else
                    <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                    <table style="width:100%; border-collapse:collapse; min-width:540px;">
                        <tbody>
                            @foreach ($byCategory as $row)
                                <tr class="ink-row">
                                    <td style="{{ $tdStyle }}; width:34%; font-weight:700;">{{ $row['name'] }}</td>
                                    <td style="{{ $tdStyle }}; width:12%; text-align:center;">{{ $row['sheets'] }} sheet{{ $row['sheets'] === 1 ? '' : 's' }}</td>
                                    <td style="{{ $tdStyle }}; width:12%; text-align:center; color:var(--ok);">{{ $row['acknowledged'] }} ack</td>
                                    <td style="{{ $tdStyle }}; width:14%; text-align:right;">{{ $row['avg_rating'] === null ? '—' : number_format($row['avg_rating'], 1) . '%' }}</td>
                                    <td style="{{ $tdStyle }}; width:28%;">
                                        @if ($row['avg_rating'] !== null)
                                            <div class="ink-bar"><div class="ink-bar-fill ink-bar-fill-ok" style="width:{{ min(100, $row['avg_rating']) }}%"></div></div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>

            {{-- By coordinator --}}
            <div class="ink-card ink-card-flush">
                <h3 class="ink-stat-label" style="padding:0.6rem 0.9rem; background:var(--row-strand); color:#f9fafb; border-bottom:2px solid var(--accent); font-size:0.68rem;">By coordinator (Line Manager 1)</h3>
                @if ($byCoordinator->isEmpty())
                    <div style="padding:1.2rem; text-align:center; color:var(--ink-faint); font-size:0.78rem;">No goal sheets for these filters yet.</div>
                @else
                    <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                    <table style="width:100%; border-collapse:collapse; min-width:480px;">
                        <tbody>
                            @foreach ($byCoordinator as $row)
                                <tr class="ink-row">
                                    <td style="{{ $tdStyle }}; font-weight:700;">{{ $row['name'] }}</td>
                                    <td style="{{ $tdStyle }}; text-align:center;">{{ $row['sheets'] }} sheet{{ $row['sheets'] === 1 ? '' : 's' }}</td>
                                    <td style="{{ $tdStyle }}; text-align:center; color:var(--ok);">{{ $row['acknowledged'] }} ack</td>
                                    <td style="{{ $tdStyle }}; text-align:right;">{{ $row['avg_rating'] === null ? '—' : number_format($row['avg_rating'], 1) . '%' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- By department --}}
        <div class="ink-card ink-card-flush">
            <h3 class="ink-stat-label" style="padding:0.6rem 0.9rem; background:var(--row-strand); color:#f9fafb; border-bottom:2px solid var(--accent); font-size:0.68rem;">By department</h3>
            @if ($byDepartment->isEmpty())
                <div style="padding:1.2rem; text-align:center; color:var(--ink-faint); font-size:0.78rem;">No goal sheets for these filters yet.</div>
            @else
                <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
                <table style="width:100%; border-collapse:collapse; min-width:560px;">
                    <tbody>
                        @foreach ($byDepartment as $row)
                            <tr class="ink-row">
                                <td style="{{ $tdStyle }}; font-weight:700; width:44%;">{{ $row['name'] }}</td>
                                <td style="{{ $tdStyle }}; width:14%; text-align:center;">{{ $row['sheets'] }} sheet{{ $row['sheets'] === 1 ? '' : 's' }}</td>
                                <td style="{{ $tdStyle }}; width:14%; text-align:center; color:var(--ok);">{{ $row['acknowledged'] }} ack</td>
                                <td style="{{ $tdStyle }}; width:14%; text-align:right;">{{ $row['avg_rating'] === null ? '—' : number_format($row['avg_rating'], 1) . '%' }}</td>
                                <td style="{{ $tdStyle }}; width:14%; text-align:right; color:var(--ink-faint);">{{ filter_var($row['avg_rating'] ?? null, FILTER_VALIDATE_FLOAT) !== false ? round(($row['avg_rating'] ?? 0) / $maxRating * 100) : 0 }}% of best</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            @endif
        </div>

        {{-- Individual sheets --}}
        <div class="ink-card ink-card-flush">
            <h3 class="ink-stat-label" style="padding:0.6rem 0.9rem; background:var(--row-strand); color:#f9fafb; border-bottom:2px solid var(--accent); font-size:0.68rem;">Individual goal sheets (open for full detail)</h3>
            <div class="ink-table-wrap ink-table-sticky-first" style="overflow-x:auto;">
                <table class="ink-table" style="min-width:980px;">
                    <thead>
                        <tr>
                            <th style="{{ $thStyle }}">Staff Member</th>
                            <th style="{{ $thStyle }}">Category</th>
                            <th style="{{ $thStyle }}">Department</th>
                            <th style="{{ $thStyle }}">Coordinator</th>
                            <th style="{{ $thStyle }}">Status</th>
                            <th style="{{ $thStyle }}; text-align:right;">Weighted Rating</th>
                            <th style="{{ $thStyle }}"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr class="ink-row">
                                <td style="{{ $tdStyle }}; font-weight:700;">{{ $row['staff'] }}</td>
                                <td style="{{ $tdStyle }}; color:var(--ink-faint);">{{ $row['category'] }}</td>
                                <td style="{{ $tdStyle }}; color:var(--ink-faint);">{{ $row['department'] }}</td>
                                <td style="{{ $tdStyle }}; color:var(--ink-faint);">{{ $row['coordinator'] }}</td>
                                <td style="{{ $tdStyle }};">
                                    <span class="ink-pill {{ $row['status_key'] === 'acknowledged' ? 'ink-pill-ok' : ($row['status_key'] === 'submitted' ? 'ink-pill-warn' : 'ink-pill-mute') }}">{{ $row['status'] }}</span>
                                    @if ($row['pending_ack'])
                                        <span class="ink-pill ink-pill-bad">ACK PENDING</span>
                                    @endif
                                </td>
                                <td style="{{ $tdStyle }}; text-align:right; font-weight:800;">{{ $row['rating'] === null ? '—' : number_format($row['rating'], 1) . '%' }}</td>
                                <td style="{{ $tdStyle }}; text-align:right;"><a href="{{ $row['url'] }}" class="ink-btn ink-btn-ghost" style="font-size:0.68rem; padding:0.2rem 0.6rem;">Open sheet</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" style="{{ $tdStyle }}; text-align:center; color:var(--ink-faint); padding:1.2rem;">No goal sheets match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
