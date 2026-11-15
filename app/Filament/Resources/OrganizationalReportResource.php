<?php

namespace App\Filament\Resources;

use App\Enums\ReportStatus;
use App\Filament\Resources\OrganizationalReportResource\Pages;
use App\Models\Branch;
use App\Models\Report;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class OrganizationalReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Overview';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $modelLabel = 'report';

    protected static ?string $slug = 'organizational-reports';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('reports.module.view') ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $count = Report::query()->pendingFor($user)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        $query = parent::getEloquentQuery()
            ->with(['user', 'branch', 'role', 'template', 'recipients'])
            ->latest('submitted_at')
            ->latest('id');

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->accessibleTo($user);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No reports yet')
            ->emptyStateDescription('Reports you submit or receive will appear here. Create one with the button above.')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Report')
                    ->searchable()
                    ->wrap()
                    ->description(fn (Report $record) => $record->is_consolidated ? 'Consolidated: ' . str_replace('_', ' ', (string) $record->compilation_kind) : null),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('From')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable()
                    ->placeholder('All branches'),
                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(fn (Report $record): string => trim(($record->period_start?->format('d/m/Y') ?? '') . ' – ' . ($record->period_end?->format('d/m/Y') ?? '')))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_consolidated')
                    ->label('Consolidated')
                    ->boolean(),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Not yet'),
                Tables\Columns\TextColumn::make('deadline')
                    ->label('Deadline')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—')
                    ->color(fn (Report $record): ?string => $record->deadline && $record->deadline->isPast() && $record->status->isUnsubmitted() ? 'danger' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(ReportStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all()),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn (): array => Branch::orderBy('name')->pluck('name', 'id')->all()),
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Submitting role')
                    ->options(fn (): array => Role::orderBy('name')->pluck('name', 'id')->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Open')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Report $record): string => static::getUrl('view', ['record' => $record])),
                Tables\Actions\Action::make('edit')
                    ->label('Continue')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (Report $record): bool => $record->isEditableBy(auth()->user()))
                    ->url(fn (Report $record): string => static::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('reports.create') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizationalReports::route('/'),
            'create' => Pages\CreateOrganizationalReport::route('/create'),
            'view' => Pages\ViewOrganizationalReport::route('/{record}'),
            'edit' => Pages\EditOrganizationalReport::route('/{record}/edit'),
        ];
    }
}
