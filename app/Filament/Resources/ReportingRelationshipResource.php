<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportingRelationshipResource\Pages;
use App\Models\ReportingRelationship;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReportingRelationshipResource extends Resource
{
    protected static ?string $model = ReportingRelationship::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?string $navigationLabel = 'Reporting Lines';

    protected static ?string $modelLabel = 'reporting relationship';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasSystemAccess() || (auth()->user()?->can('reports.routing.manage') ?? false);
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
        return $form->schema([
            Forms\Components\Section::make('Reporting line')
                ->description('Who reports to whom. Combined with Reporting Assignments this determines report routing.')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Reporting user')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('manager_user_id')
                        ->label('Direct line manager')
                        ->relationship('manager', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->different('user_id'),
                    Forms\Components\Select::make('type')
                        ->options(['direct' => 'Direct', 'secondary' => 'Secondary'])
                        ->default('direct')
                        ->required(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Reporter')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('manager.name')->label('Line manager')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('reports.routing.manage') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('reports.routing.manage') ?? false),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportingRelationships::route('/'),
            'create' => Pages\CreateReportingRelationship::route('/create'),
            'edit' => Pages\EditReportingRelationship::route('/{record}/edit'),
        ];
    }
}
