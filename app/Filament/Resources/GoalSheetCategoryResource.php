<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoalSheetCategoryResource\Pages;
use App\Models\GoalSheetCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class GoalSheetCategoryResource extends Resource
{
    protected static ?string $model = GoalSheetCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Goal Sheets';

    protected static ?string $navigationLabel = 'Workbook Categories';

    protected static ?string $modelLabel = 'workbook category';

    protected static ?string $pluralModelLabel = 'workbook categories';

    protected static ?int $navigationSort = 4;

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
            Forms\Components\Section::make('Workbook Category')
                ->description('The six worksheets of the GOAL SHEETS workbook. Goal sheets are filed under one of these categories; add more here if the school introduces new sections.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Keep the workbook spelling exactly, e.g. "CBE UPPER PRIMARY".'),
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
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('#')->sortable()->width('4rem'),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()->weight('bold'),
                Tables\Columns\TextColumn::make('code')->toggleable(),
                Tables\Columns\TextColumn::make('goal_sheets_count')->counts('goalSheets')->label('Sheets'),
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
            'index' => Pages\ListGoalSheetCategories::route('/'),
            'create' => Pages\CreateGoalSheetCategory::route('/create'),
            'edit' => Pages\EditGoalSheetCategory::route('/{record}/edit'),
        ];
    }
}
