<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Filament\Resources\AttendanceResource\RelationManagers;
use App\Models\Attendance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup= 'Academic Management';

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user?->hasUnrestrictedAccess()
            || ($user?->hasRole('Coordinators') && $user?->sectionCoordinatorAssignments()->exists());
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('year_session_id')
                    ->relationship(name: 'yearSession', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('term_id')
                    ->relationship(name: 'term', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DatePicker::make('attendance_date')
                    ->label('Attendance Date')
                    ->default(now())
                    ->required(),
                Forms\Components\Select::make('week_id')
                    ->relationship(name: 'week', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('branch')
                    ->options([
                        'Juja Road' => 'Juja Road',
                        'Kitisuru' => 'Kitisuru',
                        'South C' => 'South C',
                    ])
                    ->default(fn (): ?string => Auth::user()?->branch)
                    ->disabled(fn (): bool => ! (Auth::user()?->hasUnrestrictedAccess() ?? false))
                    ->required()
                    ->dehydrated(),
                Forms\Components\Select::make('section')
                    ->options([
                        'Early Years - EYE' => 'Early Years - EYE',
                        'Upper Primary' => 'Upper Primary',
                        'Junior School' => 'Junior School',
                    ])
                    ->options(function (): array {
                        $user = Auth::user();

                        if ($user?->hasUnrestrictedAccess()) {
                            return [
                                'Early Years - EYE' => 'Early Years - EYE',
                                'Upper Primary' => 'Upper Primary',
                                'Junior School' => 'Junior School',
                            ];
                        }

                        return $user?->sectionCoordinatorAssignments()
                            ->pluck('section', 'section')
                            ->all() ?? [];
                    })
                    ->required(),
                Forms\Components\Select::make('class_id')
                    ->relationship(name: 'class', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('stream_id')
                    ->relationship(name: 'streams', titleAttribute: 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('total_boys')
                    ->required()
                    ->numeric()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get): void {
                        $total = (int) $get('total_boys') + (int) $get('total_girls');
                        $present = (int) $get('total_present');
                        $set('class_total', $total);
                        $set('total_absent', max(0, $total - $present));
                        $set('percentage_present', $total > 0 ? round(($present / $total) * 100, 2) : 0);
                        $set('percentage_absent', $total > 0 ? round((($total - $present) / $total) * 100, 2) : 0);
                    }),
                Forms\Components\TextInput::make('total_girls')
                    ->required()
                    ->numeric()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get): void {
                        $total = (int) $get('total_boys') + (int) $get('total_girls');
                        $present = (int) $get('total_present');
                        $set('class_total', $total);
                        $set('total_absent', max(0, $total - $present));
                        $set('percentage_present', $total > 0 ? round(($present / $total) * 100, 2) : 0);
                        $set('percentage_absent', $total > 0 ? round((($total - $present) / $total) * 100, 2) : 0);
                    }),
                Forms\Components\TextInput::make('class_total')
                    ->label('Total in Class')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->readOnly(),
                Forms\Components\TextInput::make('total_present')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get): void {
                        $total = (int) $get('total_boys') + (int) $get('total_girls');
                        $present = min((int) $get('total_present'), $total);
                        $set('total_present', $present);
                        $set('total_absent', max(0, $total - $present));
                        $set('percentage_present', $total > 0 ? round(($present / $total) * 100, 2) : 0);
                        $set('percentage_absent', $total > 0 ? round((($total - $present) / $total) * 100, 2) : 0);
                    }),
                Forms\Components\TextInput::make('total_absent')
                    ->label('Total Absent (Calculated)')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->readOnly(),
                Forms\Components\TextInput::make('percentage_present')
                    ->label('Percentage Present (Calculated)')
                    ->suffix('%')
                    ->disabled()
                    ->dehydrated()
                    ->readOnly(),
                Forms\Components\TextInput::make('percentage_absent')
                    ->label('Percentage Absent (Calculated)')
                    ->suffix('%')
                    ->disabled()
                    ->dehydrated()
                    ->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('monthlyPdf')
                        ->label('Monthly Report PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (): string => route('attendances.report.pdf', ['period' => 'monthly']))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('monthlyExcel')
                        ->label('Monthly Report Excel')
                        ->icon('heroicon-o-table-cells')
                        ->url(fn (): string => route('attendances.report.excel', ['period' => 'monthly']))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('termlyPdf')
                        ->label('Termly Report PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (): string => route('attendances.report.pdf', ['period' => 'termly']))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('termlyExcel')
                        ->label('Termly Report Excel')
                        ->icon('heroicon-o-table-cells')
                        ->url(fn (): string => route('attendances.report.excel', ['period' => 'termly']))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('yearlyPdf')
                        ->label('Yearly Report PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (): string => route('attendances.report.pdf', ['period' => 'yearly']))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('yearlyExcel')
                        ->label('Yearly Report Excel')
                        ->icon('heroicon-o-table-cells')
                        ->url(fn (): string => route('attendances.report.excel', ['period' => 'yearly']))
                        ->openUrlInNewTab(),
                ])
                    ->label('Download Reports')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->button(),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('yearSession.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('term.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('week.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch')->sortable(),
                Tables\Columns\TextColumn::make('section')->badge()->sortable(),
                Tables\Columns\TextColumn::make('class.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('streams.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_boys')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_girls')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('class_total')
                    ->label('Class Total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_present')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_absent')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage_present')
                    ->label('% Present')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage_absent')
                    ->label('% Absent')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('weekly_average_percentage')
                    ->label('Weekly Avg %')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return parent::getEloquentQuery()->whereKey(0);
        }

        return parent::getEloquentQuery()
            ->with(['yearSession', 'term', 'week', 'class', 'streams'])
            ->accessibleTo($user);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
