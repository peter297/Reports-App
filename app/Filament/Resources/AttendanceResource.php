<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Academic Management';

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user?->hasUnrestrictedAccess()
            || $user?->can('view_any_attendance')
            || ($user?->hasRole('Coordinators') && $user?->sectionCoordinatorAssignments()->exists())
            ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Academic Details')
                    ->schema([
                        Forms\Components\Select::make('year_session_id')
                            ->relationship(name: 'yearSession', titleAttribute: 'name')
                            ->searchable()
                            ->preload()
                            ->default(fn (): ?int => \App\Models\YearSession::active()?->id)
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state): void {
                                $termId = $get('term_id');

                                if ($termId && (! $state || ! \App\Models\Term::whereKey($termId)->where('year_session_id', $state)->exists())) {
                                    $set('term_id', null);
                                    $set('week_id', null);

                                    return;
                                }

                                if ($termId) {
                                    $weekId = $get('week_id');

                                    if ($weekId && ! \App\Models\Week::whereKey($weekId)->where('term_id', $termId)->exists()) {
                                        $set('week_id', null);
                                    }
                                }
                            })
                            ->required(),
                        Forms\Components\Select::make('term_id')
                            ->relationship(name: 'term', titleAttribute: 'name')
                            ->options(function (Forms\Get $get): array {
                                $query = \App\Models\Term::query()->orderBy('name');

                                if ($sessionId = $get('year_session_id')) {
                                    $query->where('year_session_id', $sessionId);
                                }

                                return $query->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->preload()
                            ->default(fn (): ?int => \App\Models\Term::active()?->id)
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state): void {
                                $weekId = $get('week_id');

                                if ($weekId && (! $state || ! \App\Models\Week::whereKey($weekId)->where('term_id', $state)->exists())) {
                                    $set('week_id', null);
                                }
                            })
                            ->required(),
                        Forms\Components\Select::make('week_id')
                            ->relationship(name: 'week', titleAttribute: 'name')
                            ->options(function (Forms\Get $get): array {
                                $query = \App\Models\Week::query()->orderBy('name');

                                if ($termId = $get('term_id')) {
                                    $query->where('term_id', $termId);
                                }

                                return $query->pluck('name', 'id')->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('detectWeek')
                                    ->icon('heroicon-m-magnifying-glass-circle')
                                    ->tooltip('Detect week from the attendance date')
                                    ->action(function (Forms\Set $set, Forms\Get $get): void {
                                        static::resolveWeekFromDate($set, $get, $get('attendance_date'));
                                    })
                            )
                            ->rules([
                                function (Forms\Get $get): \Closure {
                                    return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                        if (! $value || ! $get('attendance_date')) {
                                            return;
                                        }

                                        $week = \App\Models\Week::find($value);

                                        if (! $week || ! $week->start_date || ! $week->resolved_end_date) {
                                            return;
                                        }

                                        if (! $week->containsDate($get('attendance_date'))) {
                                            $fail("The attendance date is outside the selected week's date range. Change the week or use the magnifier button to detect it.");
                                        }
                                    };
                                },
                            ]),
                        Forms\Components\DatePicker::make('attendance_date')
                            ->label('Attendance Date')
                            ->helperText('Defaults to today. You may select a previous date.')
                            ->default(fn (): string => now()->toDateString())
                            ->maxDate(now())
                            ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get, ?string $state): void {
                                if ($get('week_id')) {
                                    return;
                                }

                                static::resolveWeekFromDate($set, $get, $state);
                            })
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Location')
                    ->schema([
                        Forms\Components\Select::make('branch')
                            ->options(function (): array {
                                $user = Auth::user();

                                if ($user?->hasUnrestrictedAccess()) {
                                    return [
                                        'Juja Road' => 'Juja Road',
                                        'Kitisuru' => 'Kitisuru',
                                        'South C' => 'South C',
                                    ];
                                }

                                $branches = $user?->coordinatedBranches() ?? [];

                                return array_combine($branches, $branches);
                            })
                            ->default(function (): ?string {
                                $user = Auth::user();

                                if ($user?->hasUnrestrictedAccess()) {
                                    return $user?->branch;
                                }

                                return $user?->sectionCoordinatorAssignments()->first()?->branch;
                            })
                            ->disabled(function (): bool {
                                $user = Auth::user();

                                if ($user?->hasUnrestrictedAccess()) {
                                    return false;
                                }

                                return count($user?->coordinatedBranches() ?? []) <= 1;
                            })
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set): void {
                                $set('section', null);
                                $set('class_id', null);
                                $set('stream_id', null);
                            })
                            ->required()
                            ->dehydrated(),
                        Forms\Components\Select::make('section')
                            ->options(function (Forms\Get $get): array {
                                $user = Auth::user();

                                if ($user?->hasUnrestrictedAccess()) {
                                    return [
                                        'EYE' => 'EYE - Early Years Education',
                                        'Upper Primary' => 'Upper Primary',
                                        'Junior School' => 'Junior School',
                                    ];
                                }

                                $assignments = $user?->sectionCoordinatorAssignments();

                                if ($branch = $get('branch')) {
                                    $assignments = $assignments?->where('branch', $branch);
                                }

                                return $assignments?->pluck('section', 'section')->all() ?? [];
                            })
                            ->default(function (): ?string {
                                $user = Auth::user();

                                if ($user?->hasUnrestrictedAccess()) {
                                    return null;
                                }

                                return $user?->sectionCoordinatorAssignments()->first()?->section;
                            })
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set): void {
                                $set('class_id', null);
                                $set('stream_id', null);
                            })
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Class & Stream')
                    ->visibleOn('edit')
                    ->schema([
                        Forms\Components\Select::make('class_id')
                            ->relationship(name: 'class', titleAttribute: 'name')
                            ->options(fn (Forms\Get $get): array => static::attendanceClassOptions($get('branch'), $get('section')))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set): mixed => $set('stream_id', null))
                            ->required()
                            ->rules([
                                function (Forms\Get $get): \Closure {
                                    return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                        $user = Auth::user();

                                        if ($user?->hasUnrestrictedAccess()) {
                                            return;
                                        }

                                        if (! $user?->canManageAttendance(
                                            (string) $get('branch'),
                                            (string) $get('section'),
                                            $value ? (int) $value : null,
                                        )) {
                                            $fail('This class is not part of your assigned section.');
                                        }
                                    };
                                },
                            ]),
                        Forms\Components\Select::make('stream_id')
                            ->relationship(name: 'streams', titleAttribute: 'name')
                            ->options(fn (Forms\Get $get): array => static::attendanceStreamOptions(
                                $get('branch'),
                                $get('section'),
                                $get('class_id') ? (int) $get('class_id') : null,
                            ))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state): void {
                                static::fillFiguresFromBaseline(
                                    $set,
                                    $get,
                                    $get('branch'),
                                    $get('section'),
                                    $get('class_id') ? (int) $get('class_id') : null,
                                    $state ? (int) $state : null,
                                );
                            })
                            ->rules([
                                function (Forms\Get $get): \Closure {
                                    return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                        $user = Auth::user();

                                        if ($user?->hasUnrestrictedAccess()) {
                                            return;
                                        }

                                        if (! $user?->canManageAttendance(
                                            (string) $get('branch'),
                                            (string) $get('section'),
                                            $get('class_id') ? (int) $get('class_id') : null,
                                            $value ? (int) $value : null,
                                        )) {
                                            $fail('This stream is not part of your assigned section.');
                                        }
                                    };
                                },
                            ]),
                    ])->columns(2),
                Forms\Components\Section::make('Attendance Figures')
                    ->visibleOn('edit')
                    ->description('Class numbers pre-fill from Class Sizes when a stream is selected. Enter the number absent and present is calculated.')
                    ->schema([
                        Forms\Components\TextInput::make('total_boys')
                            ->label('Total Boys')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get): mixed => static::recalculateFromAbsent($set, $get)),
                        Forms\Components\TextInput::make('total_girls')
                            ->label('Total Girls')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get): mixed => static::recalculateFromAbsent($set, $get)),
                        Forms\Components\TextInput::make('class_total')
                            ->label('Total in Class')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->readOnly(),
                        Forms\Components\TextInput::make('total_absent')
                            ->label('Total Absent')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get): mixed => static::recalculateFromAbsent($set, $get)),
                        Forms\Components\TextInput::make('total_present')
                            ->label('Total Present (Calculated)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->readOnly(),
                        Forms\Components\TextInput::make('percentage_present')
                            ->label('% Present (Calculated)')
                            ->suffix('%')
                            ->disabled()
                            ->dehydrated()
                            ->readOnly(),
                        Forms\Components\TextInput::make('percentage_absent')
                            ->label('% Absent (Calculated)')
                            ->suffix('%')
                            ->disabled()
                            ->dehydrated()
                            ->readOnly(),
                    ])->columns(3),
                Forms\Components\Section::make('Class Entries')
                    ->description('Add one entry per class and stream to fill the whole section at once.')
                    ->visibleOn('create')
                    ->schema([
                        Forms\Components\Repeater::make('entries')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('class_id')
                                    ->label('Class')
                                    ->options(fn (Forms\Get $get): array => static::attendanceClassOptions(
                                        $get('data.branch', isAbsolute: true),
                                        $get('data.section', isAbsolute: true),
                                    ))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set): mixed => $set('stream_id', null)),
                                Forms\Components\Select::make('stream_id')
                                    ->label('Stream')
                                    ->options(fn (Forms\Get $get): array => static::attendanceStreamOptions(
                                        $get('data.branch', isAbsolute: true),
                                        $get('data.section', isAbsolute: true),
                                        $get('class_id') ? (int) $get('class_id') : null,
                                    ))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state): void {
                                        static::fillFiguresFromBaseline(
                                            $set,
                                            $get,
                                            $get('data.branch', isAbsolute: true),
                                            $get('data.section', isAbsolute: true),
                                            $get('class_id') ? (int) $get('class_id') : null,
                                            $state ? (int) $state : null,
                                            false,
                                        );
                                    }),
                                Forms\Components\TextInput::make('total_boys')
                                    ->label('Boys')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get): mixed => static::recalculateFromAbsent($set, $get, false)),
                                Forms\Components\TextInput::make('total_girls')
                                    ->label('Girls')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get): mixed => static::recalculateFromAbsent($set, $get, false)),
                                Forms\Components\TextInput::make('class_total')
                                    ->label('Total')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('total_absent')
                                    ->label('Absent')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get): mixed => static::recalculateFromAbsent($set, $get, false)),
                                Forms\Components\TextInput::make('total_present')
                                    ->label('Present (Calculated)')
                                    ->numeric()
                                    ->disabled(),
                            ])
                            ->columns(4)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('Add another class/stream')
                            ->collapsible()
                            ->itemLabel(function (array $state): ?string {
                                if (blank($state['class_id'] ?? null)) {
                                    return null;
                                }

                                $class = \App\Models\Classes::find($state['class_id']);
                                $stream = ! empty($state['stream_id'])
                                    ? \App\Models\Stream::find($state['stream_id'])
                                    : null;

                                return trim(($class?->name ?? 'Class').' '.($stream?->name ?? ''));
                            })
                            ->rules([
                                function (Forms\Get $get): \Closure {
                                    return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                        $user = Auth::user();
                                        $branch = (string) $get('branch');
                                        $section = (string) $get('section');
                                        $seen = [];
                                        $row = 0;

                                        foreach ((array) $value as $item) {
                                            $row++;

                                            if (empty($item['class_id']) || empty($item['stream_id'])) {
                                                continue;
                                            }

                                            $pair = $item['class_id'].'-'.$item['stream_id'];

                                            if (isset($seen[$pair])) {
                                                $fail("Row {$row} duplicates an earlier class and stream combination.");

                                                return;
                                            }

                                            $seen[$pair] = true;

                                            if ($user && ! $user->hasUnrestrictedAccess() && ! $user->canManageAttendance(
                                                $branch,
                                                $section,
                                                (int) $item['class_id'],
                                                (int) $item['stream_id'],
                                            )) {
                                                $fail("Row {$row} is not part of your assigned section.");

                                                return;
                                            }
                                        }
                                    };
                                },
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\ActionGroup::make([
                    // --- Period Reports ---
                    Tables\Actions\Action::make('weeklyPdf')
                        ->label('Weekly Report PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (): string => route('attendances.report.pdf', ['period' => 'weekly']))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('weeklyExcel')
                        ->label('Weekly Report Excel')
                        ->icon('heroicon-o-table-cells')
                        ->url(fn (): string => route('attendances.report.excel', ['period' => 'weekly']))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('monthlyPdf')
                        ->label('Monthly Report PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (): string => route('attendances.report.pdf', ['period' => 'monthly']))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('monthlyExcel')
                        ->label('Monthly Report Excel')
                        ->icon('heroicon-o-table-cells')
                        ->url(fn (): string => route('attendances.report.excel', ['period' => 'monthly']))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('termlyPdf')
                        ->label('Termly Report PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (): string => route('attendances.report.pdf', ['period' => 'termly']))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('termlyExcel')
                        ->label('Termly Report Excel')
                        ->icon('heroicon-o-table-cells')
                        ->url(fn (): string => route('attendances.report.excel', ['period' => 'termly']))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('yearlyPdf')
                        ->label('Yearly Report PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (): string => route('attendances.report.pdf', ['period' => 'yearly']))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('yearlyExcel')
                        ->label('Yearly Report Excel')
                        ->icon('heroicon-o-table-cells')
                        ->url(fn (): string => route('attendances.report.excel', ['period' => 'yearly']))
                        ->openUrlInNewTab(),
                ])
                    ->label('Download Reports')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->button(),

                // --- Date Range Report (Calendar) ---
                Tables\Actions\Action::make('dateRangeReportPdf')
                    ->label('Date Range PDF')
                    ->icon('heroicon-o-calendar-days')
                    ->color('danger')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From Date')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('To Date')
                            ->required()
                            ->native(false)
                            ->afterOrEqual('date_from'),
                    ])
                    ->action(function (array $data): void {
                        $this->redirect(route('attendances.report.range.pdf', [
                            'date_from' => $data['date_from'],
                            'date_to' => $data['date_to'],
                        ]));
                    }),
                Tables\Actions\Action::make('dateRangeReportExcel')
                    ->label('Date Range Excel')
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From Date')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('To Date')
                            ->required()
                            ->native(false)
                            ->afterOrEqual('date_from'),
                    ])
                    ->action(function (array $data): void {
                        $this->redirect(route('attendances.report.range.excel', [
                            'date_from' => $data['date_from'],
                            'date_to' => $data['date_to'],
                        ]));
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('yearSession.name')
                    ->label('Year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('term.name')
                    ->label('Term')
                    ->sortable(),
                Tables\Columns\TextColumn::make('week.name')
                    ->label('Week')
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch')->sortable(),
                Tables\Columns\TextColumn::make('section')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'EYE', 'Early Years - EYE' => 'info',
                        'Upper Primary' => 'success',
                        'Junior School' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('class.name')
                    ->label('Class')
                    ->sortable(),
                Tables\Columns\TextColumn::make('streams.name')
                    ->label('Stream')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_boys')
                    ->label('Boys')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_girls')
                    ->label('Girls')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('class_total')
                    ->label('Total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_present')
                    ->label('Present')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_absent')
                    ->label('Absent')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage_present')
                    ->label('% Present')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage_absent')
                    ->label('% Absent')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('week_id')
                    ->label('Week')
                    ->relationship('week', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('section')
                    ->options([
                        'EYE' => 'EYE - Early Years Education',
                        'Upper Primary' => 'Upper Primary',
                        'Junior School' => 'Junior School',
                    ]),
                Tables\Filters\SelectFilter::make('branch')
                    ->options([
                        'Juja Road' => 'Juja Road',
                        'Kitisuru' => 'Kitisuru',
                        'South C' => 'South C',
                    ]),
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Class')
                    ->relationship('class', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('stream_id')
                    ->label('Stream')
                    ->relationship('streams', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('attendance_date')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From')
                            ->native(false),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('To')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn (Builder $q, string $date) => $q->whereDate('attendance_date', '>=', $date))
                            ->when($data['date_to'], fn (Builder $q, string $date) => $q->whereDate('attendance_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('downloadSelectedPdf')
                        ->label('Download Selected PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('danger')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $ids = $records->pluck('id')->implode(',');
                            $this->redirect(route('attendances.report.selected.pdf', ['ids' => $ids]));
                        }),
                    Tables\Actions\BulkAction::make('downloadSelectedExcel')
                        ->label('Download Selected Excel')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $ids = $records->pluck('id')->implode(',');
                            $this->redirect(route('attendances.report.selected.excel', ['ids' => $ids]));
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    protected static function resolveWeekFromDate(Forms\Set $set, Forms\Get $get, ?string $state): void
    {
        if (blank($state)) {
            $set('week_id', null);

            return;
        }

        $termId = $get('term_id') ? (int) $get('term_id') : null;
        $week = \App\Models\Week::forDate($state, $termId);

        if (! $week && $termId) {
            $week = \App\Models\Week::forDate($state);

            if ($week) {
                $set('term_id', $week->term_id);
                $set('year_session_id', $week->term?->year_session_id);
            }
        }

        $set('week_id', $week?->id);
    }

    protected static function recalculateFromAbsent(Forms\Set $set, Forms\Get $get, bool $withPercentages = true): void
    {
        $total = (int) $get('total_boys') + (int) $get('total_girls');
        $absent = min((int) $get('total_absent'), $total);
        $present = max(0, $total - $absent);

        $set('class_total', $total);
        $set('total_absent', $absent);
        $set('total_present', $present);

        if ($withPercentages) {
            $set('percentage_present', $total > 0 ? round(($present / $total) * 100, 2) : 0);
            $set('percentage_absent', $total > 0 ? round((($total - $present) / $total) * 100, 2) : 0);
        }
    }

    protected static function fillFiguresFromBaseline(
        Forms\Set $set,
        Forms\Get $get,
        ?string $branch,
        ?string $section,
        ?int $classId,
        ?int $streamId,
        bool $withPercentages = true,
    ): void {
        if (! $branch || ! $section || ! $classId || ! $streamId) {
            return;
        }

        $size = \App\Models\ClassSize::query()
            ->where('branch', $branch)
            ->where('section', $section)
            ->where('class_id', $classId)
            ->where('stream_id', $streamId)
            ->first();

        if (! $size) {
            return;
        }

        $set('total_boys', $size->total_boys);
        $set('total_girls', $size->total_girls);
        $set('total_absent', 0);

        static::recalculateFromAbsent($set, $get, $withPercentages);
    }

    public static function attendanceClassOptions(?string $branch, ?string $section): array
    {
        $user = Auth::user();
        $query = \App\Models\Classes::query()->orderBy('name');

        if ($user?->hasUnrestrictedAccess()) {
            if ($branch) {
                $query->where('branch', $branch);
            }

            return $query->pluck('name', 'id')->all();
        }

        $assignments = $user?->sectionCoordinatorAssignments ?? collect();

        if ($assignments->isEmpty()) {
            return [];
        }

        $classIds = \App\Models\Stream::query()
            ->when($branch && $section,
                fn ($query): mixed => $query->where('branch', $branch)->where('section', $section),
                fn ($query): mixed => $query->where(function ($query) use ($assignments, $branch): void {
                    foreach ($assignments as $assignment) {
                        if ($branch && $assignment->branch !== $branch) {
                            continue;
                        }

                        $query->orWhere(function ($query) use ($assignment): void {
                            $query->where('branch', $assignment->branch)
                                ->where('section', $assignment->section);
                        });
                    }
                }))
            ->distinct()
            ->pluck('class_id');

        if ($classIds->isEmpty()) {
            return [];
        }

        return $query->whereIn('id', $classIds)->pluck('name', 'id')->all();
    }

    public static function attendanceStreamOptions(?string $branch, ?string $section, ?int $classId): array
    {
        $user = Auth::user();
        $query = \App\Models\Stream::query()->orderBy('name');

        if ($user?->hasUnrestrictedAccess()) {
            return $query
                ->when($branch, fn ($query): mixed => $query->where('branch', $branch))
                ->when($section, fn ($query): mixed => $query->where('section', $section))
                ->when($classId, fn ($query): mixed => $query->where('class_id', $classId))
                ->pluck('name', 'id')
                ->all();
        }

        $assignments = $user?->sectionCoordinatorAssignments ?? collect();

        if ($assignments->isEmpty()) {
            return [];
        }

        if ($branch && $section) {
            $query->where('branch', $branch)->where('section', $section);
        } else {
            $query->where(function ($query) use ($assignments, $branch): void {
                foreach ($assignments as $assignment) {
                    if ($branch && $assignment->branch !== $branch) {
                        continue;
                    }

                    $query->orWhere(function ($query) use ($assignment): void {
                        $query->where('branch', $assignment->branch)
                            ->where('section', $assignment->section);
                    });
                }
            });
        }

        if ($classId) {
            $query->where('class_id', $classId);
        }

        return $query->pluck('name', 'id')->all();
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return parent::getEloquentQuery()->whereKey(0);
        }

        return parent::getEloquentQuery()
            ->with(['yearSession', 'term', 'week', 'class', 'streams'])
            ->accessibleTo($user);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
