<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassesResource\Pages;
use App\Filament\Resources\ClassesResource\RelationManagers;
use App\Models\Classes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ClassesResource extends Resource
{
    protected static ?string $model = Classes::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'School Management';

    protected static ?string $navigationLabel = 'Classes & Streams';

    protected static ?string $modelLabel = 'class';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
<<<<<<< HEAD
        return Auth::user()?->can('academic_structure.view') ?? false;
=======
        return \App\Helpers\Utils::navigationBadge(static::class, fn (): int => static::getModel()::count());
>>>>>>> 986d183417c1db2946d5d09abadc57976e55e4ad
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Class')
                    ->schema([
                        Forms\Components\Select::make('branch_id')
                            ->label('Branch')
                            ->relationship('branch', 'name', modifyQueryUsing: fn ($query) => $query->where('is_active', true)->orderBy('name'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('academic_level_id')
                            ->label('Academic level')
                            ->relationship('academicLevel', 'name', modifyQueryUsing: fn ($query) => $query->orderBy('sort_order'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('academicLevel.name')
                    ->label('Level')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'Early Years Education' => 'info',
                        'Upper Primary' => 'success',
                        'Junior School' => 'warning',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('streams_count')
                    ->label('Streams')
                    ->counts('streams'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name'),
                Tables\Filters\SelectFilter::make('academic_level_id')
                    ->label('Academic level')
                    ->relationship('academicLevel', 'name'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('academic_structure.manage') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('academic_structure.manage') ?? false),
                ]),
            ])
            ->defaultSort('branch.name');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StreamsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClasses::route('/'),
            'create' => Pages\CreateClasses::route('/create'),
            'edit' => Pages\EditClasses::route('/{record}/edit'),
        ];
    }
}
