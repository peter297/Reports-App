<?php

namespace App\Filament\Resources;

use App\Filament\Resources\YearSessionResource\Pages;
use App\Models\Term;
use App\Models\Week;
use App\Models\YearSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class YearSessionResource extends Resource
{
    protected static ?string $model = YearSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'School Management';

    protected static ?string $navigationLabel = 'School Sessions';

    protected static ?string $modelLabel = 'school session';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('sessions.view') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Session details')
                    ->description('The school session (academic year) with its terms and weeks in one place.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Session name')
                            ->placeholder('e.g. 2027')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start date')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End date')
                            ->required()
                            ->afterOrEqual('start_date'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'upcoming' => 'Upcoming',
                                'active' => 'Active',
                                'archived' => 'Completed / Archived',
                            ])
                            ->required()
                            ->default('upcoming')
                            ->helperText('Only one session can be active at a time. Archiving keeps the session and its terms/weeks read-only.'),
                    ])
                    ->columns(4),

                Forms\Components\Section::make('Terms')
                    ->description('Each term contains its academic weeks. Week numbers must be unique within the term.')
                    ->schema([
                        Forms\Components\Repeater::make('terms')
                            ->hiddenLabel()
                            ->default([])
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => filled($state['name'] ?? null) ? $state['name'] : 'New term')
                            ->addActionLabel('Add term')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\TextInput::make('name')
                                    ->label('Term name')
                                    ->placeholder('e.g. Term 1')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Start date')
                                    ->required(),
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('End date')
                                    ->required()
                                    ->afterOrEqual('start_date'),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'upcoming' => 'Upcoming',
                                        'active' => 'Active',
                                        'completed' => 'Completed',
                                    ])
                                    ->required()
                                    ->default('upcoming'),
                                Forms\Components\Repeater::make('weeks')
                                    ->label('Weeks')
                                    ->default([])
                                    ->reorderable()
                                    ->columns(4)
                                    ->addActionLabel('Add week')
                                    ->schema([
                                        Forms\Components\Hidden::make('id'),
                                        Forms\Components\TextInput::make('number')
                                            ->label('Week #')
                                            ->numeric()
                                            ->required()
                                            ->minValue(1),
                                        Forms\Components\TextInput::make('name')
                                            ->label('Label (optional)')
                                            ->placeholder('e.g. Week 1')
                                            ->maxLength(100),
                                        Forms\Components\DatePicker::make('start_date')
                                            ->label('Start date')
                                            ->required(),
                                        Forms\Components\DatePicker::make('end_date')
                                            ->label('End date')
                                            ->required()
                                            ->afterOrEqual('start_date'),
                                    ])
                                    ->columns(1),
                            ])
                            ->columns(1),
                    ]),
            ]);
    }

    /**
     * Hydrate the repeater state from the existing record.
     */
    public static function hydrateTerms(YearSession $session): array
    {
        $session->load('terms.weeks');

        return $session->terms->map(fn (Term $term) => [
            'id' => $term->id,
            'name' => $term->name,
            'start_date' => $term->start_date?->toDateString(),
            'end_date' => $term->end_date?->toDateString(),
            'status' => $term->status,
            'weeks' => $term->weeks->map(fn (Week $week) => [
                'id' => $week->id,
                'number' => $week->number,
                'name' => $week->name,
                'start_date' => $week->start_date?->toDateString(),
                'end_date' => $week->end_date?->toDateString(),
            ])->all(),
        ])->all();
    }

    /**
     * Persist the nested terms/weeks state for a session, preserving lesson/attendance history.
     *
     * @param  array<int, array<string, mixed>>  $termsState
     */
    public static function syncTerms(YearSession $session, array $termsState): void
    {
        DB::transaction(function () use ($session, $termsState): void {
            $existingTerms = $session->terms()->get()->keyBy('id');
            $keptTermIds = [];

            foreach (array_values($termsState) as $index => $termData) {
                $name = trim((string) ($termData['name'] ?? ''));
                $start = $termData['start_date'] ?? null;
                $end = $termData['end_date'] ?? null;
                $status = $termData['status'] ?? 'upcoming';
                $termId = $termData['id'] ?? null;

                if ($name === '' || ! $start || ! $end) {
                    continue;
                }

                $term = $termId ? $existingTerms->get((int) $termId) : null;

                if (! $term) {
                    $term = new Term;
                    $term->year_session_id = $session->id;
                }

                $term->fill([
                    'name' => $name,
                    'start_date' => $start,
                    'end_date' => $end,
                    'status' => $status,
                ]);
                $term->save();
                $keptTermIds[] = $term->id;

                $existingWeeks = $term->weeks()->get()->keyBy('id');
                $keptWeekIds = [];

                foreach (array_values($termData['weeks'] ?? []) as $weekData) {
                    $number = (int) ($weekData['number'] ?? 0);
                    $weekStart = $weekData['start_date'] ?? null;
                    $weekEnd = $weekData['end_date'] ?? null;
                    $weekId = $weekData['id'] ?? null;

                    if ($number < 1 || ! $weekStart || ! $weekEnd) {
                        continue;
                    }

                    $week = $weekId ? $existingWeeks->get((int) $weekId) : null;

                    if (! $week) {
                        $week = new Week;
                        $week->term_id = $term->id;
                    }

                    $week->fill([
                        'number' => $number,
                        'name' => filled($weekData['name'] ?? null) ? trim((string) $weekData['name']) : 'Week '.$number,
                        'start_date' => $weekStart,
                        'end_date' => $weekEnd,
                    ]);
                    $week->save();
                    $keptWeekIds[] = $week->id;
                }

                $term->weeks()->whereNotIn('id', $keptWeekIds ?: [0])->delete();
            }

            $session->terms()->whereNotIn('id', $keptTermIds ?: [0])->delete();
        });
    }

    /**
     * Validate that week numbers are unique within each term before persisting.
     *
     * @param  array<int, array<string, mixed>>  $termsState
     */
    public static function validateWeekNumbers(array $termsState): void
    {
        $errors = [];

        foreach (array_values($termsState) as $termIndex => $termData) {
            $counts = [];

            foreach (array_values($termData['weeks'] ?? []) as $weekData) {
                $number = (int) ($weekData['number'] ?? 0);

                if ($number < 1) {
                    $errors["terms.{$termIndex}.weeks"] = 'Every week must have a number greater than zero.';

                    continue;
                }

                $counts[$number] = ($counts[$number] ?? 0) + 1;
            }

            foreach ($counts as $number => $count) {
                if ($count > 1) {
                    $errors["terms.{$termIndex}.weeks"] = "Week {$number} appears {$count} times in '".($termData['name'] ?? "term {$termIndex}")."'. Week numbers must be unique within a term.";
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('End')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('terms_count')
                    ->label('Terms')
                    ->counts('terms')
                    ->sortable(),
                Tables\Columns\TextColumn::make('active_term')
                    ->label('Active Term')
                    ->state(fn (YearSession $record): ?string => $record->terms()->where('status', 'active')->value('name'))
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['upcoming' => 'Upcoming', 'active' => 'Active', 'archived' => 'Archived']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('sessions.manage') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('sessions.manage') ?? false),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListYearSessions::route('/'),
            'create' => Pages\CreateYearSession::route('/create'),
            'edit' => Pages\EditYearSession::route('/{record}/edit'),
        ];
    }
}
