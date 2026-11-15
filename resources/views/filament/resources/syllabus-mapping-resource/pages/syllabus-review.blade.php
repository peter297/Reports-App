<x-filament-panels::page>
    @php
        $stats = $this->stats;
        $rows = $this->strandRows;
        $teacherOptions = $this->teacherOptions();
        $canReview = $this->canReviewMapping();
    @endphp

    <div class="ink-page">
        {{-- Header summary --}}
        <div class="ink-card" style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem;">
            <div style="display:flex; flex-wrap:wrap; gap:1.5rem;">
                @foreach (['Grade' => $this->record->grade_stream, 'Subject' => $this->record->subject->name, 'Term' => $this->record->term->name . ' · ' . $this->record->yearSession->name, 'Campus' => $this->record->branch ?? 'School-wide'] as $label => $value)
                    <div>
                        <div class="ink-stat-label">{{ $label }}</div>
                        <div style="font-weight:800; color:var(--ink-text);">{{ $value }}</div>
                    </div>
                @endforeach
            </div>
            @if (count($teacherOptions) > 1)
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <span class="ink-stat-label">Teacher</span>
                    <select wire:model.live="viewingTeacherId" class="ink-select" style="min-width:12rem;">
                        @foreach ($teacherOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <span class="ink-pill ink-pill-warn">Teacher: {{ $teacherOptions[$this->viewingTeacherId] ?? '—' }}</span>
            @endif
        </div>

        {{-- Coverage strip --}}
        <div class="ink-stats ink-stats-6">
            @foreach ([
                ['Overall coverage', number_format($stats['coverage_percent'], 2) . '%', 'ink-stat-cover'],
                ['Completed', $stats['completed_lessons'] . ' / ' . $stats['total_lessons'], 'ink-stat-ok'],
                ['Pending', $stats['pending_lessons'], 'ink-stat-pending'],
                ['Overdue', $stats['overdue_lessons'], 'ink-stat-bad'],
                ['Late lessons', $stats['late_lessons'], 'ink-stat-warn'],
                ['Gaps', count($stats['gaps']), 'ink-stat-warn'],
            ] as [$label, $value, $variant])
                <div class="ink-stat {{ $variant }}">
                    <div class="ink-stat-label">{{ $label }}</div>
                    <div class="ink-stat-value">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        @if ($stats['gaps'] !== [])
            <div class="ink-gaps">
                <strong>Out-of-sequence completion:</strong> lesson(s) {{ implode(', ', array_slice($stats['gaps'], 0, 25)) }}. Earlier lessons are pending — flag to the teacher.
            </div>
        @endif

        {{-- Strand cards --}}
        @forelse ($rows as $row)
            @php
                $strand = $row['strand'];
                $pct = min(100, max(0, (float) $row['coverage_percent']));
                $barClass = $pct >= 100 ? 'ink-bar-fill-ok' : 'ink-bar-fill';
            @endphp
            <div class="ink-card ink-card-flush">
                <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; padding:0.75rem 1rem; background:var(--row-strand); color:#f9fafb; border-bottom:2px solid var(--accent);">
                    <div>
                        <h3 style="margin:0; font-size:0.85rem; font-weight:900; text-transform:uppercase; letter-spacing:0.06em; color:var(--accent);">{{ $strand->name }}</h3>
                        <p style="margin:0.15rem 0 0; font-size:0.7rem; color:var(--ink-muted);">{{ $row['substrands'] }} substrand(s) · {{ $row['total'] }} lesson topic(s)</p>
                        @if ($strand->expected_completion_date)
                            <p style="margin:0.15rem 0 0; font-size:0.7rem; font-weight:800; color: {{ $strand->expected_completion_date->isPast() && $row['completed'] < $row['total'] ? '#f87171' : '#fcd34d' }};">
                                Expected completion: {{ $strand->expected_completion_date->format('d/m/Y') }}
                                @if ($strand->expected_completion_date->isPast() && $row['completed'] < $row['total'])
                                    · {{ (int) $strand->expected_completion_date->copy()->startOfDay()->diffInDays(now()->startOfDay()) }} day(s) past due
                                @endif
                            </p>
                        @else
                            <p style="margin:0.15rem 0 0; font-size:0.7rem; color:var(--ink-faint);">No expected date set</p>
                        @endif
                    </div>
                    <div style="display:flex; align-items:center; gap:1rem;">
                        <div style="text-align:right;">
                            <div class="ink-stat-label">Coverage</div>
                            <div style="font-size:1.5rem; font-weight:900; color:{{ $pct >= 100 ? 'var(--ok)' : ($pct >= 60 ? 'var(--accent)' : 'var(--bad)') }};">{{ number_format($pct, 2) }}%</div>
                        </div>
                        <div class="ink-bar" style="width:10rem;"><div class="ink-bar-fill {{ $barClass }}" style="width:{{ $pct }}%"></div></div>
                    </div>
                </div>

                <div class="ink-split">
                    <div style="display:grid; gap:0.4rem; align-content:start;">
                        @foreach ([
                            'Completed lessons' => $row['completed'],
                            'Pending lessons' => $row['pending'],
                            'Overdue lessons' => $row['overdue'],
                            'Completed late' => $row['late_lessons'],
                        ] as $label => $value)
                            <div style="display:flex; align-items:center; justify-content:space-between; border-bottom:1px dashed var(--ink-line); padding-bottom:0.35rem;">
                                <span style="font-size:0.75rem; color:var(--ink-muted); font-weight:600;">{{ $label }}</span>
                                <strong style="font-weight:800; color:var(--ink-text);">{{ $value }}</strong>
                            </div>
                        @endforeach
                        <div style="margin-top:0.35rem;">
                            <div class="ink-stat-label" style="margin-bottom:0.2rem;">Assessment dates</div>
                            @if ($row['assessments']->isNotEmpty())
                                <div style="display:flex; flex-wrap:wrap; gap:0.3rem;">
                                    @foreach ($row['assessments'] as $assessment)
                                        <span class="ink-pill ink-pill-info">{{ $assessment->assessment_date->format('d/m/Y') }}@if ($assessment->assessment_type)·{{ $assessment->assessment_type }}@endif</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="ink-faint" style="font-size:0.75rem;">None yet</span>
                            @endif
                        </div>
                    </div>

                    <div style="display:grid; gap:0.5rem; align-content:start;">
                        <label class="ink-stat-label" for="comment-{{ $strand->id }}">Coordinator's comment</label>
                        @if ($canReview)
                            <textarea id="comment-{{ $strand->id }}" rows="2" wire:model="comments.{{ $strand->id }}" class="ink-textarea" placeholder="e.g. Coverage is progressing as expected."></textarea>
                            @error('comments.' . $strand->id)<p style="font-size:0.65rem; color:var(--bad); font-weight:700;">{{ $message }}</p>@enderror
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:0.5rem;">
                                <span style="font-size:0.65rem; color:var(--ink-faint);">Saving retains previous comments.</span>
                                <button type="button" wire:click="saveReview({{ $strand->id }})" wire:loading.attr="disabled" class="ink-btn ink-btn-primary">Save review</button>
                            </div>
                        @else
                            <p style="margin:0; padding:0.6rem; background:var(--ink-card-strong); border:1px solid var(--ink-line); border-radius:0.5rem; font-size:0.75rem; color:var(--ink-text);">{{ $row['latest_comment'] ?? 'No comment yet.' }}</p>
                        @endif

                        @if ($row['reviews']->isNotEmpty())
                            <div style="margin-top:0.3rem;">
                                <div class="ink-stat-label" style="margin-bottom:0.2rem;">Review history</div>
                                <ul style="margin:0; padding:0; list-style:none; max-height:9rem; overflow:auto; display:grid; gap:0.3rem;">
                                    @foreach ($row['reviews'] as $review)
                                        <li style="padding:0.35rem 0.5rem; background:var(--ink-card-strong); border-radius:0.4rem; border:1px solid var(--ink-line);">
                                            <div style="display:flex; justify-content:space-between; gap:0.5rem; align-items:baseline;">
                                                <span style="font-size:0.7rem; color:var(--ink-text);">“{{ $review->comment }}”</span>
                                                <span style="font-size:0.6rem; color:var(--ink-faint); white-space:nowrap;">{{ $review->coordinator?->name }} · {{ $review->reviewed_at?->format('d/m/Y H:i') }}</span>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="ink-card" style="text-align:center; padding:2rem; color:var(--ink-faint);">This mapping has no structure yet.</div>
        @endforelse
    </div>
</x-filament-panels::page>
