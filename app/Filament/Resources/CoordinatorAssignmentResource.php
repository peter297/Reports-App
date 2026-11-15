<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoordinatorAssignmentResource\Pages;
use App\Models\CoordinatorAssignment;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CoordinatorAssignmentResource extends Resource
{
    protected static ?string $model = CoordinatorAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?string $navigationLabel = 'Coordinator Assignments';

    protected static ?string $modelLabel = 'coordinator assignment';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasSystemAccess() ?? false;
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return self::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return self::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Coordinator / Dean')
                    ->options(fn (): array => User::query()
                        ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Coordinator', 'Dean of Islamic Studies']))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->helperText('Assign a coordinator or dean of Islamic studies to one or more branch + academic level pairs instead of one role for everyone.')
                    ->required(),
                Forms\Components\Select::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('academic_level_id')
                    ->label('Academic level / department')
                    ->relationship('academicLevel', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Coordinator / Dean')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('branch.name')->label('Branch')->sortable(),
                Tables\Columns\TextColumn::make('academicLevel.name')->label('Academic level')->badge()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name'),
                Tables\Filters\SelectFilter::make('academic_level_id')
                    ->label('Academic level')
                    ->relationship('academicLevel', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('coordinator_assignments.manage') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('coordinator_assignments.manage') ?? false),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoordinatorAssignments::route('/'),
            'create' => Pages\CreateCoordinatorAssignment::route('/create'),
            'edit' => Pages\EditCoordinatorAssignment::route('/{record}/edit'),
        ];
    }
}
