<x-filament-panels::page>
    @php
        $summary = $this->summary;
        $rows = $this->mappingRows;
        $top = $this->topCoverage;
        $low = $this->lowestCoverage;
        $overdue = $this->overdueMappings;
        $notUpdated = $this->teachersNotUpdated;
        $byCampus = $this->coverageByCampus;
        $byGrade = $this->gradeBreakdown;

        $theadStyle = 'background: var(--row-strand); color: #f9fafb; font-size: 0.65rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; text-align: left;';
        $thStyle = 'padding: 0.5rem 0.7rem;';
        $tdStyle = 'padding: 0.4rem 0.7rem; border-bottom: 1px solid var(--ink-line); color: var(--ink-text); font-size: 0.75rem;';
    @endphp

    <div class="ink-page">
        @if ($this->canViewAll)
            <div class="ink-card no-print ink-filter-bar">
                <label class="ink-filter-item" for="filter-year">
                    <span class="ink-stat-label">Academic Year</span>
                    <select id="filter-year" wire:model.live="yearSessionId" class="ink-select">
                        <option value="">All years</option>
                        @foreach ($this->yearOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="ink-filter-item" for="filter-term">
                    <span class="ink-stat-label">Term</span>
                    <select id="filter-term" wire:model.live="termId" class="ink-select">
                        <option value="">All terms</option>
                        @foreach ($this->termOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="ink-filter-item" for="filter-campus">
                    <span class="ink-stat-label">Campus</span>
                    <select id="filter-campus" wire:model.live="branch" class="ink-select">
                        <option value="">All campuses</option>
                        @foreach ($this->branchOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="ink-filter-actions">
                    <a href="{{ $this->reportUrl }}" target="_blank" class="ink-btn ink-btn-solid">PDF report</a>
                    <a href="{{ $this->reportExcelUrl }}" class="ink-btn ink-btn-success">Excel report</a>
                </div>
            </div>
        @endif

        {{-- Summary cards --}}
        <div class="ink-stats ink-stats-6">
            @foreach ([
                ['Active mappings', $summary['active'], 'ink-stat-ok'],
                ['Grades tracked', $summary['grades'], 'ink-stat-info'],
                ['Subjects tracked', $summary['subjects'], 'ink-stat-info'],
                ['Overall coverage', number_format($summary['overall'], 2) . '%', 'ink-stat-cover'],
                ['Overdue subjects', $summary['overdue'], 'ink-stat-bad'],
                ['Total mappings', $summary['mappings'], 'ink-stat-total'],
            ] as [$label, $value, $variant])
                <div class="ink-stat {{ $variant }}">
                    <div class="ink-stat-label">{{ $label }}</div>
                    <div class="ink-stat-value">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        @if ($rows->isEmpty())
            <div class="ink-card" style="text-align:center; padding:2rem; color:var(--ink-faint);">No syllabus mappings match these filters yet.</div>
        @else
            <div class="ink-grid-2">
                {{-- Highest coverage --}}
                <div class="ink-card ink-card-flush">
                    <h3 class="ink-stat-label" style="padding:0.6rem 0.9rem; background:var(--row-strand); color:var(--accent); border-bottom:2px solid var(--accent);">Highest coverage</h3>
                    <table style="width:100%; border-collapse:collapse; font-size:0.75rem;">
                        @foreach ($top as $row)
                            <tr class="ink-row">
                                <td style="{{ $tdStyle }}; font-weight:700;">{{ $row['grade'] }}</td>
                                <td style="{{ $tdStyle }}; font-weight:700; color:var(--ink-text);">{{ $row['subject'] }}</td>
                                <td style="{{ $tdStyle }}; color:var(--ink-faint);">{{ $row['teacher'] ?? '—' }}</td>
                                <td style="{{ $tdStyle }}; text-align:right; font-weight:800; color:var(--ok);">{{ number_format($row['coverage_percent'], 2) }}%</td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                {{-- Lowest coverage --}}
                <div class="ink-card ink-card-flush">
                    <h3 class="ink-stat-label" style="padding:0.6rem 0.9rem; background:var(--row-strand); color:var(--bad); border-bottom:2px solid var(--bad);">Lowest coverage</h3>
                    <table style="width:100%; border-collapse:collapse; font-size:0.75rem;">
                        @foreach ($low as $row)
                            <tr class="ink-row">
                                <td style="{{ $tdStyle }}; font-weight:700;">{{ $row['grade'] }}</td>
                                <td style="{{ $tdStyle }}; font-weight:700; color:var(--ink-text);">{{ $row['subject'] }}</td>
                                <td style="{{ $tdStyle }}; color:var(--ink-faint);">{{ $row['teacher'] ?? '—' }}</td>
                                <td style="{{ $tdStyle }}; text-align:right; font-weight:800; color: {{ $row['coverage_percent'] < 40 ? 'var(--bad)' : 'var(--warn)' }};">{{ number_format($row['coverage_percent'], 2) }}%</td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                {{-- Overdue --}}
                <div class="ink-card ink-card-flush">
                    <h3 class="ink-stat-label" style="padding:0.6rem 0.9rem; background:var(--row-strand); color:var(--bad); border-bottom:2px solid var(--bad);">Subjects with overdue lessons</h3>
                    @if ($overdue->isEmpty())
                        <p style="padding:0.75rem; color:var(--ink-faint); font-size:0.75rem;">None — every scheduled lesson is completed or upcoming.</p>
                    @else
                        <table style="width:100%; border-collapse:collapse; font-size:0.75rem;">
                            @foreach ($overdue as $row)
                                <tr class="ink-row">
                                    <td style="{{ $tdStyle }}; font-weight:700;">{{ $row['grade'] }}</td>
                                    <td style="{{ $tdStyle }}; font-weight:700;"><a href="{{ \App\Filament\Resources\SyllabusMappingResource::getUrl('sheet', ['record' => $row['mapping']]) }}" style="color:var(--accent);">{{ $row['subject'] }}</a></td>
                                    <td style="{{ $tdStyle }}; color:var(--ink-faint);">{{ $row['teacher'] ?? '—' }}</td>
                                    <td style="{{ $tdStyle }}; text-align:right; font-weight:800; color:var(--bad);">{{ $row['overdue_lessons'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @endif
                </div>

                {{-- Teachers not updated --}}
                @if ($this->canViewAll)
                    <div class="ink-card ink-card-flush">
                        <h3 class="ink-stat-label" style="padding:0.6rem 0.9rem; background:var(--row-strand); color:var(--warn); border-bottom:2px solid var(--warn);">Teachers who have not updated</h3>
                        @if ($notUpdated->isEmpty())
                            <p style="padding:0.75rem; color:var(--ink-faint); font-size:0.75rem;">All assigned teachers have recorded progress.</p>
                        @else
                            <ul style="margin:0; padding:0; list-style:none; font-size:0.75rem;">
                                @foreach ($notUpdated as $entry)
                                    <li style="display:flex; justify-content:space-between; align-items:baseline; gap:1rem; padding:0.35rem 0.75rem; border-bottom:1px solid var(--ink-line); color:var(--ink-text);">
                                        <span style="font-weight:700;">{{ $entry['teacher'] }}</span>
                                        <span class="ink-faint" style="text-align:right;">{{ $entry['subjects'] ? implode(', ', $entry['subjects']) : '—' }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                @endif
            </div>

            <div class="ink-grid-2">
                @if ($this->canViewAll)
                    <div class="ink-card">
                        <h3 class="ink-stat-label" style="color:var(--accent); margin-top:0;">Coverage by campus</h3>
                        @foreach ($byCampus as $campus)
                            <div style="margin-bottom:0.6rem;">
                                <div style="display:flex; justify-content:space-between; font-size:0.75rem; margin-bottom:0.25rem;"><span style="font-weight:700; color:var(--ink-text);">{{ $campus['branch'] }}</span><span style="color:var(--ink-muted);">{{ number_format($campus['coverage'], 2) }}% · {{ $campus['mappings'] }} mapping(s)</span></div>
                                <div class="ink-bar"><div class="ink-bar-fill" style="width: {{ min(100, max(0, $campus['coverage'])) }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="ink-card">
                    <h3 class="ink-stat-label" style="color:var(--info); margin-top:0;">Coverage by grade</h3>
                    @foreach ($byGrade as $grade)
                        <div style="margin-bottom:0.6rem;">
                            <div style="display:flex; justify-content:space-between; font-size:0.75rem; margin-bottom:0.25rem;"><span style="font-weight:700; color:var(--ink-text);">{{ $grade['grade'] }}</span><span style="color:var(--ink-muted);">{{ number_format($grade['coverage'], 2) }}% · {{ $grade['subjects'] }} subject(s)</span></div>
                            <div class="ink-bar"><div class="ink-bar-fill ink-bar-fill-info" style="width: {{ min(100, max(0, $grade['coverage'])) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Full mappings table --}}
            <div class="ink-table-wrap">
                <table class="ink-table">
                    <thead><tr>
                        <th style="{{ $thStyle }}">Grade</th>
                        <th style="{{ $thStyle }}">Subject</th>
                        <th style="{{ $thStyle }}">Campus</th>
                        <th style="{{ $thStyle }}">Teacher</th>
                        <th style="{{ $thStyle }}; text-align:right;">Lessons</th>
                        <th style="{{ $thStyle }}; text-align:right;">Done</th>
                        <th style="{{ $thStyle }}; text-align:right;">Pending</th>
                        <th style="{{ $thStyle }}; text-align:right;">Overdue</th>
                        <th style="{{ $thStyle }}; text-align:right;">Coverage</th>
                        <th style="{{ $thStyle }}">Status</th>
                        <th style="{{ $thStyle }}"></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="ink-row">
                                <td style="{{ $tdStyle }}; font-weight:700;">{{ $row['grade'] }}</td>
                                <td style="{{ $tdStyle }}; font-weight:700; color:var(--ink-text);">{{ $row['subject'] }}</td>
                                <td style="{{ $tdStyle }}; color:var(--ink-muted);">{{ $row['branch'] ?? 'School-wide' }}</td>
                                <td style="{{ $tdStyle }}; color:var(--ink-muted);">{{ $row['teacher'] ?? '—' }}</td>
                                <td style="{{ $tdStyle }}; text-align:right;">{{ $row['total_lessons'] }}</td>
                                <td style="{{ $tdStyle }}; text-align:right; color:var(--ok); font-weight:700;">{{ $row['completed_lessons'] }}</td>
                                <td style="{{ $tdStyle }}; text-align:right;">{{ $row['pending_lessons'] }}</td>
                                <td style="{{ $tdStyle }}; text-align:right; font-weight:800; color: {{ $row['overdue_lessons'] > 0 ? 'var(--bad)' : 'var(--ink-text)' }};">{{ $row['overdue_lessons'] }}</td>
                                <td style="{{ $tdStyle }}; text-align:right; font-weight:800; color:var(--accent);">{{ number_format($row['coverage_percent'], 2) }}%</td>
                                <td style="{{ $tdStyle }}"><span class="ink-pill ink-pill-mute">{{ $row['status'] }}</span></td>
                                <td style="{{ $tdStyle }}"><a href="{{ \App\Filament\Resources\SyllabusMappingResource::getUrl('sheet', ['record' => $row['mapping']]) }}" style="color:var(--accent); font-weight:800;">Open ›</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
