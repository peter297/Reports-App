<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportingAssignmentResource\Pages;
use App\Models\ReportingAssignment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class ReportingAssignmentResource extends Resource
{
    protected static ?string $model = ReportingAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?string $navigationLabel = 'Reporting Assignments';

    protected static ?string $modelLabel = 'reporting assignment';

    protected static ?int $navigationSort = 4;

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
            Forms\Components\Section::make('Routing rule')
                ->description('Who should receive reports from whom. Resolved per submission and snapshotted; edits never rewrite historical reports.')
                ->schema([
                    Forms\Components\Select::make('submitter_role_id')
                        ->label('Submitting role')
                        ->options(fn (): array => Role::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Forms\Components\Select::make('branch_id')
                        ->label('Branch (optional)')
                        ->relationship('branch', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText('Limit this rule to submitters in one branch. Leave empty for all branches.')
                        ->nullable(),
                    Forms\Components\Select::make('academic_level_id')
                        ->label('Academic level (optional)')
                        ->relationship('academicLevel', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText('Limit to coordinators/deans assigned to this level. Leave empty for all levels.')
                        ->nullable(),
                    Forms\Components\Select::make('recipient_role_id')
                        ->label('Recipient role')
                        ->options(fn (): array => Role::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->required_without('recipient_user_id'),
                    Forms\Components\Select::make('recipient_user_id')
                        ->label('Recipient user')
                        ->relationship('recipientUser', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->required_without('recipient_role_id')
                        ->helperText('Pick a role, a specific user, or both.'),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('submitterRole.name')->label('Submitter role')->badge()->sortable(),
                Tables\Columns\TextColumn::make('branch.name')->label('Branch')->placeholder('All branches'),
                Tables\Columns\TextColumn::make('academicLevel.name')->label('Level')->placeholder('All levels'),
                Tables\Columns\TextColumn::make('recipientRole.name')->label('Recipient role')->placeholder('—')->badge(),
                Tables\Columns\TextColumn::make('recipientUser.name')->label('Recipient user')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('submitter_role_id')
                    ->label('Submitting role')
                    ->relationship('submitterRole', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
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
            'index' => Pages\ListReportingAssignments::route('/'),
            'create' => Pages\CreateReportingAssignment::route('/create'),
            'edit' => Pages\EditReportingAssignment::route('/{record}/edit'),
        ];
    }
}
