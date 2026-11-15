<x-filament-panels::page>
    @php
        $record = $this->record;
        $goals = $record->goals;
        $acknowledgements = $record->acknowledgements;
        $parties = \App\Models\GoalSheetAcknowledgement::PARTIES;
        $statusLabel = \App\Models\GoalSheet::STATUS_LABELS[$record->status] ?? ucfirst($record->status);
        $statusPill = match ($record->status) {
            'acknowledged' => 'ink-pill ink-pill-ok',
            'submitted' => 'ink-pill ink-pill-warn',
            default => 'ink-pill ink-pill-mute',
        };
        $weightTotal = $record->weightTotal();
        $weightOk = abs($weightTotal - 1.0) < 0.0001;
        $ratedWeight = $record->ratedWeightTotal();
        $calculated = $record->calculatedWeightedRating();
        $termAttainment = $this->termAttainment();

        $theadStyle = 'background: var(--row-strand); color: #f9fafb; font-size: 0.62rem; font-weight: 800; letter-spacing: 0.07em; text-transform: uppercase; text-align: left;';
        $thStyle = 'padding: 0.45rem 0.6rem; border-bottom: 2px solid var(--accent);';
        $tdStyle = 'padding: 0.4rem 0.6rem; border-bottom: 1px solid var(--ink-line); color: var(--ink-text); font-size: 0.72rem;';
        $avgStyle = $tdStyle . ' background: var(--accent-soft); font-weight: 800; color: var(--accent-text);';
        $trClass = 'ink-row';
        $num = fn ($value, $decimals = 2) => $value === null ? '—' : number_format((float) $value, $decimals);
    @endphp

    <div class="ink-page">

        {{-- Header bar --}}
        <div class="no-print" style="display:flex; justify-content:space-between; align-items:center; gap:0.75rem; flex-wrap:wrap;">
            <div style="font-size:0.75rem; color:var(--ink-faint);">
                {{ $record->category?->name }} · {{ $record->goal_year }} academic year
                &nbsp;·&nbsp; Sheet #{{ $record->id }}
            </div>
            <div style="display:flex; gap:0.5rem;">
                @if ($record->status === 'draft')
                    @if ($this->canSubmit())
                        <button wire:click="submit" wire:confirm="Submit this goal sheet for line manager acknowledgement? You will not be able to edit it afterwards." class="ink-btn ink-btn-solid">Submit for review</button>
                    @endif
                    <a href="{{ App\Filament\Resources\GoalSheetResource::getUrl('edit', ['record' => $record]) }}" class="ink-btn ink-btn-primary">Edit sheet</a>
                @endif
                @if (auth()->user()?->isSuperAdmin() && $record->status !== 'draft')
                    <button wire:click="reopen" class="ink-btn ink-btn-ghost">Re-open for editing</button>
                @endif
                <button onclick="window.print()" class="ink-btn ink-btn-ghost">Print</button>
            </div>
        </div>

        {{-- Staff header card --}}
        <div class="ink-card" style="display:flex; justify-content:space-between; gap:1.5rem; flex-wrap:wrap;">
            <div style="min-width:280px;">
                <div style="display:flex; gap:0.6rem; align-items:center;">
                    <span class="ink-pill ink-pill-info">{{ $record->category?->name }}</span>
                    <span class="{{ $statusPill }}">{{ $statusLabel }}</span>
                    @if ($record->pendingAcknowledgement())
                        <span class="ink-pill ink-pill-warn">Pending acknowledgement</span>
                    @endif
                </div>
                <h1 style="margin:0.5rem 0 0.15rem; font-size:1.5rem; line-height:1.2; color:var(--ink-text);">{{ $record->staff_name }}</h1>
                <div style="color:var(--ink-faint); font-size:0.8rem;">{{ $record->job_title ?? 'Staff member' }} @if($record->department) · {{ $record->department }} @endif</div>
                <div style="margin-top:0.75rem; font-size:0.75rem; color:var(--ink-text); display:grid; grid-template-columns:auto 1fr; gap:0.2rem 0.9rem;">
                    <span class="ink-stat-label" style="align-self:center;">Line Manager 1</span>
                    <span>{{ $record->coordinator?->name ?? $record->coordinator_name ?? '—' }}</span>
                    <span class="ink-stat-label" style="align-self:center;">Line Manager 2</span>
                    <span>{{ $record->lineManager2?->name ?? $record->line_manager_2_name ?? '—' }}</span>
                    <span class="ink-stat-label" style="align-self:center;">Goal Year</span>
                    <span>{{ $record->goal_year }}</span>
                    <span class="ink-stat-label" style="align-self:center;">Staff Account</span>
                    <span>{{ $record->staff?->email ?? 'Not linked (imported)' }}</span>
                    @if ($record->submitted_at)
                        <span class="ink-stat-label" style="align-self:center;">Submitted</span>
                        <span>{{ $record->submitted_at->format('d M Y, H:i') }}</span>
                    @endif
                </div>
            </div>
            <div class="gs-header-meta" style="min-width:280px; border-left:1px solid var(--ink-line); padding-left:1.5rem;">
                <div class="ink-stat-label" style="margin-bottom:0.4rem;">Sheet totals</div>
                <div style="display:grid; gap:0.35rem; font-size:0.78rem; color:var(--ink-text);">
                    <div style="display:flex; justify-content:space-between;"><span>Goals</span><strong>{{ $goals->count() }}</strong></div>
                    <div style="display:flex; justify-content:space-between;"><span>Subject/class rows</span><strong>{{ $goals->sum(fn ($g) => $g->entries->count()) }}</strong></div>
                    <div style="display:flex; justify-content:space-between;"><span>Performance weight</span><strong style="color: {{ $weightOk ? 'var(--ok)' : 'var(--bad)' }};">{{ number_format($weightTotal, 2) }}{{ $weightOk ? '' : ' (expected 1.00)' }}</strong></div>
                </div>
            </div>
        </div>

        {{-- Ratings strip --}}
        <div class="ink-stats ink-stats-4">
            <div class="ink-stat {{ $weightOk ? 'ink-stat-ok' : 'ink-stat-warn' }}">
                <div class="ink-stat-label">Performance Weight</div>
                <div class="ink-stat-value">{{ number_format($weightTotal, 2) }}</div>
            </div>
            <div class="ink-stat ink-stat-info">
                <div class="ink-stat-label">Attainment coverage</div>
                <div class="ink-stat-value">{{ number_format($ratedWeight, 2) }}<span style="font-size:0.6em;"> / 1.00</span></div>
            </div>
            <div class="ink-stat ink-stat-cover">
                <div class="ink-stat-label">Weighted Rating (calculated)</div>
                <div class="ink-stat-value">{{ $calculated === null ? '—' : number_format($calculated, 1) . '%' }}</div>
            </div>
            <div class="ink-stat ink-stat-total">
                <div class="ink-stat-label">Weighted Rating (end of year)</div>
                <div class="ink-stat-value">{{ $record->end_of_year_rating === null ? '—' : number_format((float) $record->end_of_year_rating, 1) . '%' }}</div>
            </div>
        </div>

        {{-- Term attainment strip --}}
        <div class="ink-stats ink-stats-3">
            <div class="ink-stat ink-stat-info">
                <div class="ink-stat-label">Term 1 attainment (END T1)</div>
                <div class="ink-stat-value">{{ $termAttainment['term_1'] === null ? '—' : number_format($termAttainment['term_1'], 1) . '%' }}</div>
            </div>
            <div class="ink-stat ink-stat-info">
                <div class="ink-stat-label">Term 2 attainment (END T2)</div>
                <div class="ink-stat-value">{{ $termAttainment['term_2'] === null ? '—' : number_format($termAttainment['term_2'], 1) . '%' }}</div>
            </div>
            <div class="ink-stat ink-stat-info">
                <div class="ink-stat-label">Term 3 attainment (END T3)</div>
                <div class="ink-stat-value">{{ $termAttainment['term_3'] === null ? '—' : number_format($termAttainment['term_3'], 1) . '%' }}</div>
            </div>
        </div>

        {{-- Goals --}}
        @foreach ($goals as $goal)
            @php
                $avg = $this->columnAverages($goal);
                $attainment = $goal->attainment();
            @endphp
            <div class="ink-card ink-card-flush" style="margin-bottom:0;">
                <div style="padding:0.7rem 0.9rem; border-bottom:2px solid var(--accent); display:flex; justify-content:space-between; gap:1rem; flex-wrap:wrap; align-items:flex-start;">
                    <div style="min-width:280px;">
                        <div style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
                            <span class="ink-stat-label" style="font-size:0.75rem;">Goal {{ $goal->goal_number ?? $loop->iteration }}</span>
                            <span class="ink-pill ink-pill-info">{{ $goal->category_name }}</span>
                            <span class="ink-pill {{ $goal->isLowerBetter() ? 'ink-pill-warn' : 'ink-pill-ok' }}">{{ $goal->achievement_type }}{{ $goal->isLowerBetter() ? ' · lower is better' : ' · higher is better' }}</span>
                            <span class="ink-pill ink-pill-mute">Weight {{ $goal->performance_weight === null ? '—' : number_format((float) $goal->performance_weight, 2) }}</span>
                        </div>
                        <div style="margin-top:0.45rem; color:var(--ink-text); font-size:0.82rem; line-height:1.45; max-width:62ch;">{{ $goal->goal_statement }}</div>
                        <div style="margin-top:0.4rem; font-size:0.68rem; color:var(--ink-faint); display:flex; gap:1rem; flex-wrap:wrap;">
                            <span><strong class="ink-stat-label">UOM</strong> {{ $goal->uom ?? '%' }}</span>
                            <span><strong class="ink-stat-label">Goal for year</strong> {{ $num($goal->goal_for_year, 1) }}%</span>
                            <span><strong class="ink-stat-label">Source</strong> {{ $goal->source_of_data ?? '—' }}</span>
                            @if ($goal->calculation_method)
                                <span style="flex-basis:100%;"><strong class="ink-stat-label">Calculation</strong> {{ $goal->calculation_method }}</span>
                            @endif
                        </div>
                    </div>
                    <div style="text-align:right; min-width:140px;">
                        <div class="ink-stat-label">Goal attainment</div>
                        <div style="font-size:1.6rem; font-weight:800; color: {{ $attainment === null ? 'var(--ink-faint)' : 'var(--ok)' }};">{{ $attainment === null ? '—' : number_format($attainment, 1) . '%' }}</div>
                        <div class="ink-stat-label" style="margin-top:0.25rem;">Weighted {{ $attainment === null ? '—' : number_format((float) $goal->performance_weight * $attainment, 2) }}</div>
                    </div>
                </div>

                <div class="ink-table-wrap ink-table-sticky-first" style="overflow-x:auto;">
                    <table class="ink-table" style="min-width:1050px;">
                        <thead>
                            <tr style="{{ $theadStyle }}">
                                <th style="{{ $thStyle }}">Subject</th>
                                <th style="{{ $thStyle }}">Class</th>
                                <th style="{{ $thStyle }}; text-align:right;">TERM 1</th>
                                <th style="{{ $thStyle }}; text-align:right;">TERM 2</th>
                                <th style="{{ $thStyle }}; text-align:right;">TERM 3</th>
                                <th style="{{ $thStyle }}; text-align:right; border-left:2px solid var(--accent);">OP T1</th>
                                <th style="{{ $thStyle }}; text-align:right;">MID T1</th>
                                <th style="{{ $thStyle }}; text-align:right;">END T1</th>
                                <th style="{{ $thStyle }}; text-align:right;">MID T2</th>
                                <th style="{{ $thStyle }}; text-align:right;">END T2</th>
                                <th style="{{ $thStyle }}; text-align:right;">MID T3</th>
                                <th style="{{ $thStyle }}; text-align:right;">END T3</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($goal->entries as $entry)
                                <tr class="{{ $trClass }}">
                                    <td style="{{ $tdStyle }}; font-weight:700;">{{ $entry->subject_name ?: '—' }}</td>
                                    <td style="{{ $tdStyle }}; color:var(--ink-faint);">{{ $entry->class_name ?: '—' }}</td>
                                    <td style="{{ $tdStyle }}; text-align:right;">{{ $num($entry->term_1) === '—' ? '—' : $num($entry->term_1) . '%' }}</td>
                                    <td style="{{ $tdStyle }}; text-align:right;">{{ $num($entry->term_2) === '—' ? '—' : $num($entry->term_2) . '%' }}</td>
                                    <td style="{{ $tdStyle }}; text-align:right;">{{ $num($entry->term_3) === '—' ? '—' : $num($entry->term_3) . '%' }}</td>
                                    <td style="{{ $tdStyle }}; text-align:right; border-left:2px solid var(--accent);">{{ $num($entry->op_t1) === '—' ? '—' : $num($entry->op_t1) . '%' }}</td>
                                    <td style="{{ $tdStyle }}; text-align:right;">{{ $num($entry->mid_t1) === '—' ? '—' : $num($entry->mid_t1) . '%' }}</td>
                                    <td style="{{ $tdStyle }}; text-align:right;">{{ $num($entry->end_t1) === '—' ? '—' : $num($entry->end_t1) . '%' }}</td>
                                    <td style="{{ $tdStyle }}; text-align:right;">{{ $num($entry->mid_t2) === '—' ? '—' : $num($entry->mid_t2) . '%' }}</td>
                                    <td style="{{ $tdStyle }}; text-align:right;">{{ $num($entry->end_t2) === '—' ? '—' : $num($entry->end_t2) . '%' }}</td>
                                    <td style="{{ $tdStyle }}; text-align:right;">{{ $num($entry->mid_t3) === '—' ? '—' : $num($entry->mid_t3) . '%' }}</td>
                                    <td style="{{ $tdStyle }}; text-align:right;">{{ $num($entry->end_t3) === '—' ? '—' : $num($entry->end_t3) . '%' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="12" style="{{ $tdStyle }}; text-align:center; color:var(--ink-faint); padding:0.9rem;">No subject/class rows recorded for this goal yet.</td></tr>
                            @endforelse
                            @if ($goal->entries->isNotEmpty())
                                <tr>
                                    <td colspan="2" style="{{ $avgStyle }}">AVERAGE (workbook formula)</td>
                                    <td style="{{ $avgStyle }}; text-align:right;">{{ $num($avg['term_1']) === '—' ? '—' : $num($avg['term_1']) . '%' }}</td>
                                    <td style="{{ $avgStyle }}; text-align:right;">{{ $num($avg['term_2']) === '—' ? '—' : $num($avg['term_2']) . '%' }}</td>
                                    <td style="{{ $avgStyle }}; text-align:right;">{{ $num($avg['term_3']) === '—' ? '—' : $num($avg['term_3']) . '%' }}</td>
                                    <td style="{{ $avgStyle }}; text-align:right; border-left:2px solid var(--accent);">{{ $num($avg['op_t1']) === '—' ? '—' : $num($avg['op_t1']) . '%' }}</td>
                                    <td style="{{ $avgStyle }}; text-align:right;">{{ $num($avg['mid_t1']) === '—' ? '—' : $num($avg['mid_t1']) . '%' }}</td>
                                    <td style="{{ $avgStyle }}; text-align:right;">{{ $num($avg['end_t1']) === '—' ? '—' : $num($avg['end_t1']) . '%' }}</td>
                                    <td style="{{ $avgStyle }}; text-align:right;">{{ $num($avg['mid_t2']) === '—' ? '—' : $num($avg['mid_t2']) . '%' }}</td>
                                    <td style="{{ $avgStyle }}; text-align:right;">{{ $num($avg['end_t2']) === '—' ? '—' : $num($avg['end_t2']) . '%' }}</td>
                                    <td style="{{ $avgStyle }}; text-align:right;">{{ $num($avg['mid_t3']) === '—' ? '—' : $num($avg['mid_t3']) . '%' }}</td>
                                    <td style="{{ $avgStyle }}; text-align:right;">{{ $num($avg['end_t3']) === '—' ? '—' : $num($avg['end_t3']) . '%' }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        @if ($goals->isEmpty())
            <div class="ink-card" style="text-align:center; color:var(--ink-faint); padding:1.5rem;">No goals recorded on this sheet yet.</div>
        @endif

        {{-- Acknowledgement & sign-off --}}
        <div class="ink-card">
            <h2 class="ink-stat-label" style="font-size:0.72rem; margin-bottom:0.6rem;">Acknowledgement &amp; Sign-off</h2>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:0.75rem;">
                @foreach ($parties as $party => $label)
                    @php
                        $ack = $acknowledgements->firstWhere('party', $party);
                        $signed = (bool) ($ack?->signed);
                        $expectedName = match ($party) {
                            'owner' => $record->staff_name,
                            'line_manager_1' => $record->coordinator?->name ?? $record->coordinator_name,
                            'line_manager_2' => $record->lineManager2?->name ?? $record->line_manager_2_name,
                        };
                    @endphp
                    <div style="border:1px solid var(--ink-line); border-radius:0.5rem; padding:0.75rem 0.9rem; background:{{ $signed ? 'var(--ok-soft)' : 'transparent' }};">
                        <div class="ink-stat-label">{{ $label }}</div>
                        <div style="margin:0.35rem 0; font-weight:700; color:var(--ink-text); font-size:0.85rem;">{{ $ack?->signed ? $ack->signer_name : ($expectedName ?? '—') }}</div>
                        @if ($signed)
                            <div class="ink-pill ink-pill-ok">Signed {{ $ack->signed_at?->format('d M Y, H:i') }}</div>
                            @if ($ack->notes)
                                <div style="margin-top:0.4rem; font-size:0.7rem; color:var(--ink-faint);">{{ $ack->notes }}</div>
                            @endif
                        @else
                            <div class="ink-pill ink-pill-mute">Awaiting signature</div>
                            @if (! $this->signatureParty && $record->status !== 'draft' && $this->signableParty() === $party)
                                <div class="no-print" style="margin-top:0.5rem;">
                                    <button wire:click="startSignature('{{ $party }}')" class="ink-btn ink-btn-success" style="width:100%;">Sign acknowledgement</button>
                                </div>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Signature capture --}}
            @if ($this->signatureParty)
                <div class="no-print" style="margin-top:0.9rem; border:1px solid var(--accent); border-radius:0.5rem; padding:0.8rem 0.9rem;">
                    <div class="ink-stat-label" style="margin-bottom:0.4rem;">Sign as {{ $parties[$this->signatureParty] }}</div>
                    <div class="sig-grid">
                        <label style="grid-column:1 / -1;">
                            <span class="ink-stat-label">Typed signature (full name)</span>
                            <input type="text" wire:model="signatureName" class="ink-input" style="width:100%;" autocomplete="off">
                            @error('signatureName') <span style="color:var(--bad); font-size:0.7rem;">{{ $message }}</span> @enderror
                        </label>
                        <label style="grid-column:1 / -1;">
                            <span class="ink-stat-label">Notes (optional)</span>
                            <input type="text" wire:model="signatureNotes" class="ink-input" style="width:100%;" placeholder="e.g. Reviewed at mid-year meeting">
                        </label>
                        <div style="display:flex; gap:0.5rem;">
                            <button wire:click="confirmSignature" class="ink-btn ink-btn-success">Confirm signature</button>
                            <button wire:click="cancelSignature" class="ink-btn ink-btn-ghost">Cancel</button>
                        </div>
                    </div>
                </div>
            @endif
            <div style="margin-top:0.7rem; font-size:0.68rem; color:var(--ink-faint);">
                Signatures in the paper workbook are captured here digitally. Signing records your account, your typed name and the time. Line Manager 1 and Line Manager 2 must both sign before the sheet is marked Acknowledged.
            </div>
        </div>
    </div>
</x-filament-panels::page>
