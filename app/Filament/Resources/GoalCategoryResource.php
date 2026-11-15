<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoalCategoryResource\Pages;
use App\Models\GoalCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class GoalCategoryResource extends Resource
{
    protected static ?string $model = GoalCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Goal Sheets';

    protected static ?string $navigationLabel = 'Goal Categories & Measures';

    protected static ?string $modelLabel = 'goal category';

    protected static ?string $pluralModelLabel = 'goal categories & measures';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('goalsheets.admin') ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('goalsheets.admin') ?? false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->can('goalsheets.admin') ?? false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->can('goalsheets.admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Goal Category')
                ->description('Fully configurable: the workbook ships with Academic Performance, Reading Skills, Writing Skills and Learner Discipline, but any category and measure can be added or retired here.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('code')
                        ->maxLength(50),
                    Forms\Components\TextInput::make('description')
                        ->maxLength(500)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->default(0),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])->columns(['default' => 1, 'md' => 2]),

            Forms\Components\Section::make('Default Performance Measures')
                ->description('Pre-fill values applied when this category is picked on a goal sheet.')
                ->schema([
                    Forms\Components\Repeater::make('measures')
                        ->relationship('measures')
                        ->orderColumn('sort_order')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Measure name')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(['default' => 'full', 'lg' => 4]),
                            Forms\Components\TextInput::make('uom')
                                ->label('UOM')
                                ->default('%')
                                ->maxLength(20)
                                ->columnSpan(['default' => 'full', 'lg' => 1]),
                            Forms\Components\Select::make('achievement_type')
                                ->label('Achv (HB/LB)')
                                ->options(['HB' => 'HB', 'LB' => 'LB'])
                                ->default('HB')
                                ->required()
                                ->columnSpan(['default' => 'full', 'lg' => 1]),
                            Forms\Components\Textarea::make('goal_statement')
                                ->label('Goal statement / performance measure')
                                ->rows(2)
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('calculation_method')
                                ->label('Calculation method')
                                ->rows(2)
                                ->columnSpan(['default' => 'full', 'lg' => 4]),
                            Forms\Components\TextInput::make('source_of_data')
                                ->label('Source of data')
                                ->maxLength(255)
                                ->columnSpan(['default' => 'full', 'lg' => 2]),
                            Forms\Components\Grid::make(['default' => 1, 'md' => 4])->schema([
                                Forms\Components\TextInput::make('goal_for_year')
                                    ->label('Goal for the year')
                                    ->numeric()
                                    ->suffix('%')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('performance_weight')
                                    ->label('Performance weight')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(1)
                                    ->step(0.05)
                                    ->columnSpan(1),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->columnSpan(1),
                            ]),
                        ])
                        ->columns(['default' => 1, 'lg' => 6])
                        ->collapsible()
                        ->addActionLabel('Add measure'),
                ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('#')->sortable()->width('4rem'),
                Tables\Columns\TextColumn::make('name')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('code')->toggleable(),
                Tables\Columns\TextColumn::make('measures_count')->counts('measures')->label('Measures'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoalCategories::route('/'),
            'create' => Pages\CreateGoalCategory::route('/create'),
            'edit' => Pages\EditGoalCategory::route('/{record}/edit'),
        ];
    }
}
