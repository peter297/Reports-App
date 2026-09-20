<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
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

    protected static ?string $navigationGroup = 'Academic Management';

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
                Forms\Components\Section::make('Academic Details')
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
                        Forms\Components\Select::make('week_id')
                            ->relationship(name: 'week', titleAttribute: 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\DatePicker::make('attendance_date')
                            ->label('Attendance Date')
                            ->default(now())
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Location')
                    ->schema([
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
                            ->options(function (): array {
                                $user = Auth::user();

                                if ($user?->hasUnrestrictedAccess()) {
                                    return [
                                        'EYE' => 'EYE - Early Years Education',
                                        'Upper Primary' => 'Upper Primary',
                                        'Junior School' => 'Junior School',
                                    ];
                                }

                                return $user?->sectionCoordinatorAssignments()
                                    ->pluck('section', 'section')
                                    ->all() ?? [];
                            })
                            ->required(),
                    ])->columns(2),
                Forms\Components\Section::make('Class & Stream')
                    ->schema([
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
                    ])->columns(2),
                Forms\Components\Section::make('Attendance Figures')
                    ->schema([
                        Forms\Components\TextInput::make('total_boys')
                            ->label('Total Boys')
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
                            ->label('Total Girls')
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
                            ->label('Total Present')
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
                            ->label('% Present (Calculated)')
                            ->suffix('%')
                            ->disabled()
                            ->dehydrated()
                            ->readOnly(),
                        Forms\Components\TextInput::make('percentage_absent')
                            ->label('% Absent (Calculated)')
                            ->suffix('%')
                            ->disabled()
                            ->dehydrated()
                            ->readOnly(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\ActionGroup::make([
                    // --- Period Reports ---
                    Tables\Actions\Action::make('weeklyPdf')
                        ->label('Weekly Report PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(fn (): string => route('attendances.report.pdf', ['period' => 'weekly']))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('weeklyExcel')
                        ->label('Weekly Report Excel')
                        ->icon('heroicon-o-table-cells')
                        ->url(fn (): string => route('attendances.report.excel', ['period' => 'weekly']))
                        ->openUrlInNewTab(),
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

                // --- Date Range Report (Calendar) ---
                Tables\Actions\Action::make('dateRangeReportPdf')
                    ->label('Date Range PDF')
                    ->icon('heroicon-o-calendar-days')
                    ->color('danger')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From Date')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('To Date')
                            ->required()
                            ->native(false)
                            ->afterOrEqual('date_from'),
                    ])
                    ->action(function (array $data): void {
                        $this->redirect(route('attendances.report.range.pdf', [
                            'date_from' => $data['date_from'],
                            'date_to' => $data['date_to'],
                        ]));
                    }),
                Tables\Actions\Action::make('dateRangeReportExcel')
                    ->label('Date Range Excel')
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From Date')
                            ->required()
                            ->native(false),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('To Date')
                            ->required()
                            ->native(false)
                            ->afterOrEqual('date_from'),
                    ])
                    ->action(function (array $data): void {
                        $this->redirect(route('attendances.report.range.excel', [
                            'date_from' => $data['date_from'],
                            'date_to' => $data['date_to'],
                        ]));
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('yearSession.name')
                    ->label('Year')
                    ->sortable(),
                Tables\Columns\TextColumn::make('term.name')
                    ->label('Term')
                    ->sortable(),
                Tables\Columns\TextColumn::make('week.name')
                    ->label('Week')
                    ->sortable(),
                Tables\Columns\TextColumn::make('attendance_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch')->sortable(),
                Tables\Columns\TextColumn::make('section')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'EYE', 'Early Years - EYE' => 'info',
                        'Upper Primary' => 'success',
                        'Junior School' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('class.name')
                    ->label('Class')
                    ->sortable(),
                Tables\Columns\TextColumn::make('streams.name')
                    ->label('Stream')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_boys')
                    ->label('Boys')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_girls')
                    ->label('Girls')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('class_total')
                    ->label('Total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_present')
                    ->label('Present')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_absent')
                    ->label('Absent')
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
                Tables\Filters\SelectFilter::make('week_id')
                    ->label('Week')
                    ->relationship('week', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('section')
                    ->options([
                        'EYE' => 'EYE - Early Years Education',
                        'Upper Primary' => 'Upper Primary',
                        'Junior School' => 'Junior School',
                    ]),
                Tables\Filters\SelectFilter::make('branch')
                    ->options([
                        'Juja Road' => 'Juja Road',
                        'Kitisuru' => 'Kitisuru',
                        'South C' => 'South C',
                    ]),
                Tables\Filters\SelectFilter::make('class_id')
                    ->label('Class')
                    ->relationship('class', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('stream_id')
                    ->label('Stream')
                    ->relationship('streams', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('attendance_date')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('From')
                            ->native(false),
                        Forms\Components\DatePicker::make('date_to')
                            ->label('To')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn (Builder $q, string $date) => $q->whereDate('attendance_date', '>=', $date))
                            ->when($data['date_to'], fn (Builder $q, string $date) => $q->whereDate('attendance_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('downloadSelectedPdf')
                        ->label('Download Selected PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('danger')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $ids = $records->pluck('id')->implode(',');
                            $this->redirect(route('attendances.report.selected.pdf', ['ids' => $ids]));
                        }),
                    Tables\Actions\BulkAction::make('downloadSelectedExcel')
                        ->label('Download Selected Excel')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $ids = $records->pluck('id')->implode(',');
                            $this->redirect(route('attendances.report.selected.excel', ['ids' => $ids]));
                        }),
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
