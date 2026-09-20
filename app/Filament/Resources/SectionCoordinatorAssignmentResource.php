<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SectionCoordinatorAssignmentResource\Pages;
use App\Models\SectionCoordinatorAssignment;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SectionCoordinatorAssignmentResource extends Resource
{
    protected static ?string $model = SectionCoordinatorAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Academic Management';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasUnrestrictedAccess() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Coordinator')
                    ->options(fn (): array => User::query()
                        ->whereHas('roles', fn ($query) => $query->where('name', 'Coordinators'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('branch')
                    ->options([
                        'Juja Road' => 'Juja Road',
                        'Kitisuru' => 'Kitisuru',
                        'South C' => 'South C',
                    ])
                    ->required(),
                Forms\Components\Select::make('section')
                    ->options([
                        'EYE' => 'EYE - Early Years Education',
                        'Upper Primary' => 'Upper Primary',
                        'Junior School' => 'Junior School',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Coordinator')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('branch')->sortable(),
                Tables\Columns\TextColumn::make('section')->badge()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSectionCoordinatorAssignments::route('/'),
            'create' => Pages\CreateSectionCoordinatorAssignment::route('/create'),
            'edit' => Pages\EditSectionCoordinatorAssignment::route('/{record}/edit'),
        ];
    }
}
