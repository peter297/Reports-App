<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoalSheetResource\Pages;
use App\Models\GoalCategory;
use App\Models\GoalMeasure;
use App\Models\GoalSheet;
use App\Models\GoalSheetCategory;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GoalSheetResource extends Resource
{
    protected static ?string $model = GoalSheet::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Goal Sheets';

    protected static ?string $navigationLabel = 'Goal Sheets';

    protected static ?string $modelLabel = 'goal sheet';

    protected static ?string $pluralModelLabel = 'goal sheets';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('goalsheets.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('goalsheets.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();

        return (bool) ($user?->can('goalsheets.edit') && $record->isEditableBy($user));
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();

        if (! ($user?->can('goalsheets.delete'))) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $record->status === GoalSheet::STATUS_DRAFT
            && ((int) $record->staff_user_id === (int) $user->id || $user->hasUnrestrictedAccess());
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->can('goalsheets.delete') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery()
            ->with(['category', 'staff', 'coordinator', 'lineManager2', 'goals.entries', 'acknowledgements']);

        if (! $user) {
            return $query->whereKey(0);
        }

        return $query->accessibleTo($user);
    }

    /** Options for user pickers: all active staff, labelled with name + email. */
    protected static function staffOptions(): array
    {
        return User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (User $user) => [$user->id => $user->name . ' (' . $user->email . ')'])
            ->all();
    }

    protected static function categoryOptions(): array
    {
        return GoalSheetCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->all();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Goal Sheet')
                    ->description('The six workbook sections (CBE/ICDC × E.Y.E / Upper Primary / Junior School) are the goal sheet categories.')
                    ->schema([
                        Forms\Components\Select::make('goal_sheet_category_id')
                            ->label('Workbook Category')
                            ->options(fn () => static::categoryOptions())
                            ->searchable()
                            ->required()
                            ->helperText('Which worksheet this sheet belongs to.'),
                        Forms\Components\TextInput::make('goal_year')
                            ->label('Goal Sheet Year')
                            ->numeric()
                            ->integer()
                            ->minValue(2000)
                            ->maxValue(2100)
                            ->default(fn () => (int) now()->format('Y'))
                            ->required(),
                        Forms\Components\Placeholder::make('status_display')
                            ->label('Status')
                            ->content(fn ($record) => $record
                                ? ucfirst($record->status) . ($record->submitted_at ? ' on ' . $record->submitted_at->format('d M Y, H:i') : '')
                                : 'Draft (new sheet)'),
                        Forms\Components\Select::make('staff_user_id')
                            ->label('Staff Account')
                            ->options(fn () => static::staffOptions())
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->helperText('Link the sheet to a system staff account. Leave empty for imported sheets whose staff member is not in the system.')
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state && $user = User::find($state)) {
                                    $set('staff_name', $user->name);
                                }
                            }),
                        Forms\Components\TextInput::make('staff_name')
                            ->label('Staff Member Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('job_title')
                            ->label('Job Title')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('department')
                            ->label('Department')
                            ->maxLength(255),
                        Forms\Components\Select::make('coordinator_id')
                            ->label('Line Manager 1 (Coordinator)')
                            ->options(fn () => static::staffOptions())
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('coordinator_name', $state ? (User::find($state)?->name ?? '') : '');
                            }),
                        Forms\Components\TextInput::make('coordinator_name')
                            ->label('Coordinator Name')
                            ->helperText('Filled automatically from the account; keep the imported value when the coordinator has no account.')
                            ->maxLength(255),
                        Forms\Components\Select::make('line_manager_2_id')
                            ->label('Line Manager 2 (Deputy)')
                            ->options(fn () => static::staffOptions())
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $set('line_manager_2_name', $state ? (User::find($state)?->name ?? '') : '');
                            }),
                        Forms\Components\TextInput::make('line_manager_2_name')
                            ->label('Line Manager 2 Name')
                            ->maxLength(255),
                    ])->columns(['default' => 1, 'md' => 3]),

                Forms\Components\Section::make('Goals')
                    ->description('Pick a category to pre-fill the default measure from the configuration; every value stays editable. Performance weights should total 1.00.')
                    ->schema([
                        Forms\Components\Repeater::make('goals')
                            ->relationship('goals')
                            ->orderColumn('position')
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => isset($state['category_name']) && $state['category_name'] !== ''
                                ? ($state['category_name'] . (isset($state['performance_weight']) && $state['performance_weight'] !== null && $state['performance_weight'] !== '' ? ' — weight ' . $state['performance_weight'] : ''))
                                : 'Goal')
                            ->schema([
                                Forms\Components\Grid::make(['default' => 1, 'md' => 6])->schema([
                                    Forms\Components\TextInput::make('goal_number')
                                        ->label('No')
                                        ->numeric()
                                        ->integer()
                                        ->minValue(1)
                                        ->columnSpan(['default' => 'full', 'md' => 1]),
                                    Forms\Components\Select::make('goal_category_id')
                                        ->label('Category')
                                        ->options(fn () => GoalCategory::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id')->all())
                                        ->searchable()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            $category = GoalCategory::query()->with(['measures' => fn ($q) => $q->where('is_active', true)])->find($state);
                                            $measure = $category?->measures->first();

                                            if (! $category) {
                                                return;
                                            }

                                            $set('category_name', $category->name);

                                            if ($measure) {
                                                $set('goal_statement', $measure->goal_statement);
                                                $set('uom', $measure->uom ?? '%');
                                                $set('calculation_method', $measure->calculation_method);
                                                $set('source_of_data', $measure->source_of_data);
                                                $set('achievement_type', $measure->achievement_type ?? 'HB');
                                                $set('performance_weight', $measure->performance_weight);
                                                $set('goal_for_year', $measure->goal_for_year);
                                            }
                                        })
                                        ->columnSpan(['default' => 'full', 'md' => 2]),
                                    Forms\Components\Hidden::make('category_name'),
                                    Forms\Components\TextInput::make('uom')
                                        ->label('UOM')
                                        ->default('%')
                                        ->maxLength(20)
                                        ->columnSpan(['default' => 'full', 'md' => 1]),
                                    Forms\Components\Select::make('achievement_type')
                                        ->label('Achv (HB/LB)')
                                        ->options(['HB' => 'HB — higher is better', 'LB' => 'LB — lower is better'])
                                        ->default('HB')
                                        ->required()
                                        ->columnSpan(['default' => 'full', 'md' => 2]),
                                ]),
                                Forms\Components\Textarea::make('goal_statement')
                                    ->label('Goal Statement / Performance Measures')
                                    ->rows(2)
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\Grid::make(['default' => 1, 'md' => 4])->schema([
                                    Forms\Components\TextInput::make('goal_for_year')
                                        ->label('Goal for the Year')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(10000)
                                        ->suffix('%')
                                        ->helperText('E.g. 80 means 80%.')
                                        ->columnSpan(['default' => 'full', 'md' => 1]),
                                    Forms\Components\TextInput::make('performance_weight')
                                        ->label('Performance Weight')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(1)
                                        ->step(0.05)
                                        ->helperText('Fraction, e.g. 0.5.')
                                        ->columnSpan(['default' => 'full', 'md' => 1]),
                                    Forms\Components\TextInput::make('source_of_data')
                                        ->label('Source of Data')
                                        ->maxLength(255)
                                        ->columnSpan(['default' => 'full', 'md' => 2]),
                                ]),
                                Forms\Components\Textarea::make('calculation_method')
                                    ->label('Calculation Method')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('entries')
                                    ->relationship('entries')
                                    ->orderColumn('position')
                                    ->label('Subject / Class Rows (termly targets and monthly checkpoints)')
                                    ->schema([
                                        Forms\Components\Grid::make(['default' => 1, 'md' => 12])->schema([
                                            Forms\Components\TextInput::make('subject_name')
                                                ->label('Subject')
                                                ->placeholder('Leave blank for non-subject goals')
                                                ->columnSpan(['default' => 'full', 'md' => 4]),
                                            Forms\Components\TextInput::make('class_name')
                                                ->label('Class')
                                                ->placeholder('e.g. Grade 4 Blue')
                                                ->columnSpan(['default' => 'full', 'md' => 2]),
                                            Forms\Components\TextInput::make('term_1')
                                                ->label('TERM 1')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(10000)
                                                ->suffix('%')
                                                ->columnSpan(['default' => 'full', 'md' => 2]),
                                            Forms\Components\TextInput::make('term_2')
                                                ->label('TERM 2')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(10000)
                                                ->suffix('%')
                                                ->columnSpan(['default' => 'full', 'md' => 2]),
                                            Forms\Components\TextInput::make('term_3')
                                                ->label('TERM 3')
                                                ->numeric()
                                                ->minValue(0)
                                                ->maxValue(10000)
                                                ->suffix('%')
                                                ->columnSpan(['default' => 'full', 'md' => 2]),
                                        ]),
                                        Forms\Components\Grid::make(['default' => 2, 'md' => 7])->schema([
                                            Forms\Components\TextInput::make('op_t1')->label('OP T1')->numeric()->minValue(0)->maxValue(10000)->suffix('%'),
                                            Forms\Components\TextInput::make('mid_t1')->label('MID T1')->numeric()->minValue(0)->maxValue(10000)->suffix('%'),
                                            Forms\Components\TextInput::make('end_t1')->label('END T1')->numeric()->minValue(0)->maxValue(10000)->suffix('%'),
                                            Forms\Components\TextInput::make('mid_t2')->label('MID T2')->numeric()->minValue(0)->maxValue(10000)->suffix('%'),
                                            Forms\Components\TextInput::make('end_t2')->label('END T2')->numeric()->minValue(0)->maxValue(10000)->suffix('%'),
                                            Forms\Components\TextInput::make('mid_t3')->label('MID T3')->numeric()->minValue(0)->maxValue(10000)->suffix('%'),
                                            Forms\Components\TextInput::make('end_t3')->label('END T3')->numeric()->minValue(0)->maxValue(10000)->suffix('%'),
                                        ]),
                                    ])
                                    ->itemLabel(fn (array $state): ?string => trim(($state['subject_name'] ?? '') . ' ' . ($state['class_name'] ?? '')) ?: 'Overall row')
                                    ->addActionLabel('Add subject/class row')
                                    ->reorderable()
                                    ->columnSpanFull(),
                            ]),
                    ]),

                Forms\Components\Section::make('End of Year')
                    ->description('The workbook fills the Weighted Rating at END OF YEAR.')
                    ->schema([
                        Forms\Components\TextInput::make('end_of_year_rating')
                            ->label('Weighted Rating (end of year)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->helperText('Recorded manually at year end. The calculated weighted rating (from recorded results) is shown on the view page and the dashboard.'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2),
                    ])->columns(['default' => 1, 'md' => 2])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('staff_name')
                    ->label('Staff')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (GoalSheet $record) => $record->department),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('goal_year')
                    ->label('Year')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('job_title')
                    ->label('Job Title')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('coordinator_name')
                    ->label('Coordinator')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => GoalSheet::STATUS_LABELS[$state] ?? ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        GoalSheet::STATUS_DRAFT => 'gray',
                        GoalSheet::STATUS_SUBMITTED => 'warning',
                        GoalSheet::STATUS_ACKNOWLEDGED => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('calculatedWeightedRating')
                    ->label('Weighted Rating')
                    ->state(fn (GoalSheet $record) => $record->calculatedWeightedRating())
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : number_format((float) $state, 1) . '%')
                    ->color(fn ($state) => $state === null ? 'gray' : 'success')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('acknowledgements_count')
                    ->label('Acknowledged')
                    ->state(fn (GoalSheet $record) => $record->acknowledgements->where('signed', true)->count() . '/3')
                    ->alignCenter()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d M Y')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('goal_sheet_category_id')
                    ->label('Category')
                    ->options(fn () => static::categoryOptions()),
                Tables\Filters\SelectFilter::make('goal_year')
                    ->label('Year')
                    ->options(fn () => GoalSheet::query()->distinct()->orderByDesc('goal_year')->pluck('goal_year', 'goal_year')->all()),
                Tables\Filters\SelectFilter::make('status')
                    ->options(GoalSheet::STATUS_LABELS),
                Tables\Filters\SelectFilter::make('coordinator_id')
                    ->label('Coordinator')
                    ->options(fn () => User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('goal_year', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoalSheets::route('/'),
            'create' => Pages\CreateGoalSheet::route('/create'),
            'view' => Pages\ViewGoalSheet::route('/{record}'),
            'edit' => Pages\EditGoalSheet::route('/{record}/edit'),
        ];
    }
}
