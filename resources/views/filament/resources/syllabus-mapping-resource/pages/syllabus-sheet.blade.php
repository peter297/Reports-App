<x-filament-panels::page>
    @php
        $stats = $this->stats;
        $groups = $this->lessonGroups;
        $strandCoverage = $this->strandCoverage;
        $teacherOptions = $this->teacherOptions();
        $canEdit = $this->canEditProgress;
        $coordinatorComments = collect($strandCoverage)->map(fn ($row) => $row['strand'])->mapWithKeys(fn ($strand) => [$strand->id => $strand->reviews()->with('coordinator')->latest('reviewed_at')->first()])->filter()->all();
    @endphp

    <div class="ink-page">
        {{-- Action bar --}}
        <div class="ink-card no-print" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:0.75rem;">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:0.6rem;">
                <span style="font-size:0.7rem; font-weight:800; letter-spacing:0.08em; text-transform:uppercase; color:var(--ink-faint);">Viewing as</span>
                @if (count($teacherOptions) > 1 || ! $this->viewingTeacherId)
                    <select wire:model.live="viewingTeacherId" class="ink-select" style="min-width:12rem;">
                        @forelse ($teacherOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @empty
                            <option value="">No teacher assigned</option>
                        @endforelse
                    </select>
                @else
                    <span style="font-size:0.85rem; font-weight:800; color:var(--ink-text);">{{ $teacherOptions[$this->viewingTeacherId] ?? '—' }}</span>
                @endif
            </div>
            <div style="display:flex; flex-wrap:wrap; gap:0.5rem;">
                <a href="{{ route('syllabus.reports.mapping.pdf', ['mapping' => $this->record->id, 'teacher_id' => $this->viewingTeacherId]) }}" target="_blank" class="ink-btn ink-btn-solid">PDF</a>
                <a href="{{ route('syllabus.reports.mapping.excel', ['mapping' => $this->record->id, 'teacher_id' => $this->viewingTeacherId]) }}" class="ink-btn ink-btn-success">Excel</a>
                <button type="button" onclick="window.print()" class="ink-btn ink-btn-ghost">Print</button>
                @if ($canEdit)
                    <button type="button" wire:click="saveAll" wire:loading.attr="disabled" wire:target="saveAll" class="ink-btn ink-btn-primary">Save all changes</button>
                @endif
            </div>
        </div>

        {{-- Summary cards --}}
        <div class="ink-stats">
            @foreach ([
                ['Total lessons', $stats['total_lessons'], 'ink-stat-total'],
                ['Completed', $stats['completed_lessons'], 'ink-stat-ok'],
                ['Pending', $stats['pending_lessons'], 'ink-stat-pending'],
                ['Overdue', $stats['overdue_lessons'], 'ink-stat-bad'],
                ['Late', $stats['late_lessons'], 'ink-stat-warn'],
                ['Coverage', number_format($stats['coverage_percent'], 2) . '%', 'ink-stat-cover'],
                ['Consecutive', $stats['highest_consecutive'] . ' / ' . $stats['total_lessons'], 'ink-stat-info'],
            ] as [$label, $value, $variant])
                <div class="ink-stat {{ $variant }}">
                    <div class="ink-stat-label">{{ $label }}</div>
                    <div class="ink-stat-value">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        @if ($stats['gaps'] !== [])
            <div class="ink-gaps">
                <strong>Gaps detected:</strong> lesson(s) {{ implode(', ', array_slice($stats['gaps'], 0, 25)) }}{{ count($stats['gaps']) > 25 ? '…' : '' }} are completed out of sequence. Coordinators should review.
            </div>
        @endif

        {{-- Assessments --}}
        <div class="ink-card no-print">
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:0.75rem;">
                <h3 class="ink-stat-label" style="margin:0;">Assessments</h3>
                @if ($canEdit)
                    <div style="display:flex; flex-wrap:wrap; align-items:flex-end; gap:0.5rem;">
                        <label style="display:grid; gap:0.2rem;">
                            <span class="ink-stat-label">Date</span>
                            <input type="date" wire:model="assessmentDate" max="{{ now()->toDateString() }}" class="ink-input">
                            @error('assessmentDate')<span style="font-size:0.65rem; color:var(--bad);">{{ $message }}</span>@enderror
                        </label>
                        <label style="display:grid; gap:0.2rem;">
                            <span class="ink-stat-label">Type</span>
                            <input type="text" wire:model="assessmentType" placeholder="CAT, End of strand…" class="ink-input" style="min-width:9rem;">
                        </label>
                        <label style="display:grid; gap:0.2rem;">
                            <span class="ink-stat-label">Description</span>
                            <input type="text" wire:model="assessmentDescription" placeholder="e.g. Strand 1 test" class="ink-input" style="min-width:12rem;">
                        </label>
                        <button type="button" wire:click="recordAssessment" class="ink-btn ink-btn-info">Record for checked lessons ({{ count($this->selectedLessons) }})</button>
                    </div>
                @endif
            </div>
            @if ($this->assessments->isNotEmpty())
                <div style="display:flex; flex-wrap:wrap; gap:0.4rem; margin-top:0.6rem;">
                    @foreach ($this->assessments as $assessment)
                        <span class="ink-pill ink-pill-info" title="{{ $assessment->description }}">
                            {{ $assessment->assessment_date->format('d/m/Y') }}
                            @if ($assessment->assessment_type)· {{ $assessment->assessment_type }}@endif
                            · {{ $assessment->lessons_count }} topic(s)
                            @if ($assessment->createdBy)· by {{ $assessment->createdBy->name }}@endif
                        </span>
                    @endforeach
                </div>
            @else
                <p class="ink-faint" style="margin:0.4rem 0 0; font-size:0.8rem;">No assessments yet. Tick the completed lessons, then record one.</p>
            @endif
        </div>

        {{-- Mapping table --}}
        <div class="ink-table-wrap">
            <table class="ink-table">
                <thead>
                    <tr>
                        <th style="width:2rem; text-align:center;">#</th>
                        <th style="width:3rem; text-align:center;">Out Of</th>
                        <th>Theme / Strand</th>
                        <th>Substrand</th>
                        <th style="min-width:14rem;">Lesson Topic</th>
                        <th style="width:7rem;">Date of Completion</th>
                        <th style="width:5rem; text-align:center;">Late by</th>
                        <th style="width:4rem; text-align:center;">% Cover</th>
                        <th style="min-width:11rem;">Teacher's Comment</th>
                        <th style="width:7rem;">Assessment</th>
                        <th style="min-width:10rem;">Coordinator's Comment</th>
                        <th class="no-print" style="width:3rem; text-align:center;"><button type="button" wire:click="selectAllLessons" class="ink-pill ink-pill-warn" style="cursor:pointer;border:none;">All</button></th>
                        <th class="no-print" style="width:5rem;">Status</th>
                        <th class="no-print" style="min-width:8rem;">Last Updated</th>
                        <th class="no-print" style="width:3rem;"></th>
                    </tr>
                </thead>
                @forelse ($groups as $strandId => $strandGroup)
                    @php
                        $strand = $strandGroup['strand'];
                        $row = $strandCoverage[$strandId] ?? null;
                        $review = $coordinatorComments[$strandId] ?? null;
                    @endphp
                    <tbody x-data="{ open: true }">
                        <tr class="ink-strand">
                            <td colspan="15">
                                <button type="button" class="ink-strand-inner" x-on:click="open = ! open">
                                    <span class="ink-strand-name">
                                        {{ $strand->name }}
                                        @if ($strand->description) <span style="text-transform:none; font-weight:400; color:var(--ink-muted);">— {{ $strand->description }}</span>@endif
                                        @if ($strand->expected_completion_date)
                                            <span class="ink-strand-expected" style="margin-left:0.4rem;">Expected {{ $strand->expected_completion_date->format('d/m/Y') }}</span>
                                        @else
                                            <span style="margin-left:0.4rem; font-weight:600; color:var(--ink-faint); text-transform:none;">No expected date</span>
                                        @endif
                                    </span>
                                    <span class="ink-strand-meta">
                                        @if ($row)
                                            <span>{{ $row['completed'] }}/{{ $row['total'] }} done</span>
                                            <span style="color:var(--accent);">{{ $row['coverage_percent'] }}%</span>
                                        @endif
                                        <span x-text="open ? '▾' : '▸'"></span>
                                    </span>
                                </button>
                            </td>
                        </tr>
                        @foreach ($strandGroup['substrands'] as $substrandId => $substrandGroup)
                            @php $substrand = $substrandGroup['substrand']; @endphp
                            <tr class="ink-substrand" x-show="open">
                                <td colspan="15">{{ $substrand->name }}</td>
                            </tr>
                            @foreach ($substrandGroup['lessons'] as $lesson)
                                @php
                                    $p = $this->progress[$lesson->id] ?? ['completion' => null, 'comment' => null];
                                    $completed = ! empty($p['completion']);
                                    $strandExpected = $strand->expected_completion_date;
                                    $coverage = $this->record->total_lessons > 0 && $completed ? round(($lesson->lesson_number / $this->record->total_lessons) * 100, 2) : 0.0;
                                    $lateDays = $strandExpected && $completed
                                        ? (int) $strandExpected->copy()->startOfDay()->diffInDays(\Carbon\Carbon::parse($p['completion'])->startOfDay(), false)
                                        : null;
                                    $status = \App\Enums\SyllabusLessonStatus::fromProgress($p['completion'], $strandExpected?->toDateString());
                                    $lastUpdated = $this->lastUpdatedByLesson[$lesson->id] ?? '—';
                                    $rowClass = $completed ? 'ink-row ink-row-done' : 'ink-row';
                                    $pillClass = match ($status) {
                                        \App\Enums\SyllabusLessonStatus::Completed => 'ink-pill-ok',
                                        \App\Enums\SyllabusLessonStatus::Overdue => 'ink-pill-bad',
                                        \App\Enums\SyllabusLessonStatus::Upcoming => 'ink-pill-warn',
                                        \App\Enums\SyllabusLessonStatus::NotScheduled => 'ink-pill-mute',
                                    };
                                @endphp
                                <tr class="{{ $rowClass }}" x-show="open">
                                    <td style="text-align:center; font-weight:900; color:var(--accent);">{{ $lesson->lesson_number }}</td>
                                    <td style="text-align:center; color:var(--ink-faint);">{{ $this->record->total_lessons }}</td>
                                    <td class="ink-muted">{{ $strand->name }}</td>
                                    <td class="ink-muted">{{ $substrand->name }}</td>
                                    <td style="font-weight:700; color:var(--ink-text);">{{ $lesson->topic }}</td>
                                    <td>
                                        <input type="date" class="ink-input" wire:model="progress.{{ $lesson->id }}.completion" {{ $canEdit ? '' : 'disabled' }} style="width:100%;">
                                    </td>
                                    <td style="text-align:center;">
                                        @if ($completed && ! $strandExpected)
                                            <span class="ink-faint">—</span>
                                        @elseif ($completed && $lateDays !== null && $lateDays > 0)
                                            <span class="ink-pill ink-pill-bad">{{ $lateDays }}d late</span>
                                        @elseif ($completed)
                                            <span class="ink-pill ink-pill-ok">On time</span>
                                        @elseif ($strandExpected && $strandExpected->isPast())
                                            <span class="ink-pill ink-pill-warn">{{ (int) $strandExpected->copy()->startOfDay()->diffInDays(now()->startOfDay()) }}d over</span>
                                        @else
                                            <span class="ink-faint">—</span>
                                        @endif
                                    </td>
                                    <td style="text-align:center; font-weight:800; font-variant-numeric:tabular-nums; color: {{ $completed ? 'var(--ok)' : 'var(--ink-faint)' }};">{{ number_format($coverage, 2) }}%</td>
                                    <td>
                                        <input type="text" class="ink-input" wire:model="progress.{{ $lesson->id }}.comment" placeholder="Comment…" {{ $canEdit ? '' : 'disabled' }} style="width:100%;">
                                    </td>
                                    <td style="color:var(--info); font-weight:600;">{{ $this->assessmentDatesByLesson[$lesson->id] ?? '—' }}</td>
                                    <td class="ink-muted">{{ $review?->comment ?? '—' }}</td>
                                    <td class="no-print" style="text-align:center;"><input type="checkbox" style="width:1rem;height:1rem;accent-color:var(--accent);" wire:model="selectedLessons" value="{{ $lesson->id }}"></td>
                                    <td class="no-print"><span class="ink-pill {{ $pillClass }}">{{ $status->label() }}</span></td>
                                    <td class="ink-faint no-print" style="font-size:0.65rem;">{{ $lastUpdated }}</td>
                                    <td class="no-print" style="text-align:center;">
                                        @if ($canEdit)
                                            <button type="button" wire:click="saveRow({{ $lesson->id }})" wire:loading.attr="disabled" wire:target="saveRow({{ $lesson->id }})" class="ink-btn ink-btn-success" style="padding:0.3rem 0.55rem; font-size:0.65rem;">Save</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                @empty
                    <tbody><tr><td colspan="15" style="padding:2rem; text-align:center; color:var(--ink-faint);">This mapping has no lessons yet. Ask an administrator to configure the syllabus structure.</td></tr></tbody>
                @endforelse
            </table>
        </div>

        @if ($canEdit)
            <div class="no-print" style="display:flex; justify-content:flex-end;">
                <button type="button" wire:click="saveAll" wire:loading.attr="disabled" class="ink-btn ink-btn-primary" style="font-size:0.85rem; padding:0.6rem 1.25rem;">Save all changes</button>
            </div>
        @endif
    </div>
</x-filament-panels::page>
