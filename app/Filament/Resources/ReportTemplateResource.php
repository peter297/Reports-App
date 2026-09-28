<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportTemplateResource\Pages;
use App\Models\ReportTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ReportTemplateResource extends Resource
{
    protected static ?string $model = ReportTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?string $navigationLabel = 'Manage Templates';

    protected static ?string $slug = 'report-templates';

    protected static ?string $modelLabel = 'Report Template';

    protected static ?string $pluralModelLabel = 'Manage Templates';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->hasUnrestrictedAccess() || $user?->can('view_any_report::template')) ?? false;
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return ($user?->hasUnrestrictedAccess() || $user?->can('create_report::template')) ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();

        return ($user?->hasUnrestrictedAccess() || $user?->can('update_report::template')) ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        $user = auth()->user();

        return ($user?->hasUnrestrictedAccess() || $user?->can('delete_report::template')) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Template Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g. Monthly Attendance Report'),

                        Forms\Components\Select::make('category')
                            ->options(collect(\App\Models\ReportTemplate::CATEGORIES)
                                ->mapWithKeys(fn (string $category): array => [$category => $category])
                                ->all())
                            ->default('Report Template')
                            ->required()
                            ->live(),

                        Forms\Components\Select::make('file_type')
                            ->options([
                                'word' => 'Word Document (.docx)',
                                'excel' => 'Excel Spreadsheet (.xlsx)',
                            ])
                            ->required(fn (Forms\Get $get): bool => $get('category') !== 'Trackers'),

                        Forms\Components\Textarea::make('description')
                            ->nullable()
                            ->rows(3)
                            ->placeholder('Brief description of this template...'),

                        Forms\Components\TextInput::make('link_url')
                            ->label('Tracker Link (Google Sheet URL)')
                            ->url()
                            ->visible(fn (Forms\Get $get): bool => $get('category') === 'Trackers')
                            ->required(fn (Forms\Get $get): bool => $get('category') === 'Trackers')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('roles')
                            ->label('Visible To Roles (empty = everyone)')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->visible(fn (): bool => auth()->user()?->hasUnrestrictedAccess() ?? false)
                            ->dehydrated(fn (): bool => auth()->user()?->hasUnrestrictedAccess() ?? false)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('File Upload')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Template File')
                            ->disk('public')
                            ->directory('report-templates')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/msword',
                                'application/vnd.ms-excel',
                            ])
                            ->maxSize(10240) // 10MB
                            ->required(fn (Forms\Get $get): bool => $get('category') !== 'Trackers')
                            ->downloadable()
                            ->openable()
                            ->previewable(false)
                            ->afterStateUpdated(function (Forms\Set $set, ?string $state): void {
                                if ($state) {
                                    $extension = strtolower(pathinfo($state, PATHINFO_EXTENSION));
                                    $fileType = match (true) {
                                        in_array($extension, ['doc', 'docx']) => 'word',
                                        in_array($extension, ['xls', 'xlsx']) => 'excel',
                                        default => null,
                                    };

                                    if ($fileType) {
                                        $set('file_type', $fileType);
                                    }

                                    $set('file_size', Storage::disk('public')->exists($state)
                                        ? Storage::disk('public')->size($state)
                                        : null,
                                    );
                                }
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Trackers' => 'info',
                        'MAL' => 'warning',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Visible To')
                    ->badge()
                    ->limitList(3)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('file_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'word' => 'info',
                        'excel' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'word' => 'Word',
                        'excel' => 'Excel',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('formatted_size')
                    ->label('Size')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(collect(\App\Models\ReportTemplate::CATEGORIES)
                        ->mapWithKeys(fn (string $category): array => [$category => $category])
                        ->all()),
                Tables\Filters\SelectFilter::make('file_type')
                    ->options([
                        'word' => 'Word',
                        'excel' => 'Excel',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('openLink')
                    ->label('Open Tracker')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (\App\Models\ReportTemplate $record): string => (string) $record->link_url)
                    ->openUrlInNewTab()
                    ->visible(fn (\App\Models\ReportTemplate $record): bool => filled($record->link_url)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return parent::getEloquentQuery()->whereKey(0);
        }

        return parent::getEloquentQuery()->visibleTo($user);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportTemplates::route('/'),
            'create' => Pages\CreateReportTemplate::route('/create'),
            'edit' => Pages\EditReportTemplate::route('/{record}/edit'),
        ];
    }
}
