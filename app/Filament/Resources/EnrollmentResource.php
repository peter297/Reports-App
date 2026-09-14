<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnrollmentResource\Pages;
use App\Models\Classes;
use App\Models\Enrollment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EnrollmentResource extends Resource
{
    protected static ?string $model = Enrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Admins Area';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->hasUnrestrictedAccess() || $user?->can('view_any_enrollment')) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('branch')
                    ->options([
                        'Juja Road' => 'Juja Road',
                        'Kitisuru' => 'Kitisuru',
                        'South C' => 'South C',
                    ])
                    ->default(fn (): ?string => auth()->user()?->branch)
                    ->disabled(fn (): bool => !(auth()->user()?->hasUnrestrictedAccess() ?? false))
                    ->dehydrated()
                    ->required(),
                Forms\Components\Select::make('year_session_id')
                    ->label('Academic Year')
                    ->relationship('yearSession', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('term_id')
                    ->relationship('term', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('total_learners')
                    ->label('Total Number of Learners (Calculated)')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->readOnly()
                    ->default(0),
                Forms\Components\Repeater::make('class_breakdown')
                    ->label('Learners per Class')
                    ->schema([
                        Forms\Components\Select::make('class_id')
                            ->label('Class')
                            ->options(fn (): array => Classes::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('boys')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get): void {
                                $set('total', (int) $get('boys') + (int) $get('girls'));
                            })
                            ->required(),
                        Forms\Components\TextInput::make('girls')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get): void {
                                $set('total', (int) $get('boys') + (int) $get('girls'));
                            })
                            ->required(),
                        Forms\Components\TextInput::make('total')
                            ->label('Total Learners')
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->readOnly()
                            ->default(0),
                        Forms\Components\TextInput::make('new_admissions')
                            ->label('New Admissions')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('departures')
                            ->label('Learners Who Have Left')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->addActionLabel('Add class')
                    ->collapsible()
                    ->afterStateUpdated(function (Forms\Set $set, ?array $state): void {
                        $set('total_learners', collect($state ?? [])->sum(
                            fn (array $class): int => (int) ($class['boys'] ?? 0) + (int) ($class['girls'] ?? 0),
                        ));
                    })
                    ->required(),
                Forms\Components\Textarea::make('comment')
                    ->label('Comment')
                    ->rows(4)
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\Action::make('exportPdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (): string => route('enrollments.pdf'))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('exportExcel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-table-cells')
                    ->url(fn (): string => route('enrollments.excel'))
                    ->openUrlInNewTab(),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('branch')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('yearSession.name')->label('Academic Year')->sortable(),
                Tables\Columns\TextColumn::make('term.name')->sortable(),
                Tables\Columns\TextColumn::make('total_learners')
                    ->label('Total Learners')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('All branches'),
                    ),
                Tables\Columns\ViewColumn::make('class_breakdown')
                    ->label('Learners per Class')
                    ->view('filament.tables.columns.class-breakdown'),
                Tables\Columns\TextColumn::make('new_admissions')->sortable(),
                Tables\Columns\TextColumn::make('departures')->label('Learners Left')->sortable(),
                Tables\Columns\TextColumn::make('comment')->limit(40)->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch')
                    ->options([
                        'Juja Road' => 'Juja Road',
                        'Kitisuru' => 'Kitisuru',
                        'South C' => 'South C',
                    ]),
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

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        if (! $user) {
            return parent::getEloquentQuery()->whereKey(0);
        }

        return parent::getEloquentQuery()->accessibleTo($user);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnrollments::route('/'),
            'create' => Pages\CreateEnrollment::route('/create'),
            'edit' => Pages\EditEnrollment::route('/{record}/edit'),
        ];
    }
}
