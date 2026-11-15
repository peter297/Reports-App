<?php

namespace App\Filament\Resources;

use App\Enums\ReportFrequency;
use App\Filament\Resources\ReportTemplateResource\Pages;
use App\Models\ReportTemplate;
use App\Models\User;
use App\Services\ReportDataSources;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
<<<<<<< HEAD
use Spatie\Permission\Models\Role;
=======
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
>>>>>>> 986d183417c1db2946d5d09abadc57976e55e4ad

class ReportTemplateResource extends Resource
{
    protected static ?string $model = ReportTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'System Settings';

<<<<<<< HEAD
    protected static ?string $navigationLabel = 'Report Templates';
=======
    protected static ?string $navigationLabel = 'Manage Templates';

    protected static ?string $slug = 'report-templates';
>>>>>>> 986d183417c1db2946d5d09abadc57976e55e4ad

    protected static ?string $slug = 'report-templates-admin';

<<<<<<< HEAD
    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'report template';
=======
    protected static ?string $pluralModelLabel = 'Manage Templates';
>>>>>>> 986d183417c1db2946d5d09abadc57976e55e4ad

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasSystemAccess() || (auth()->user()?->can('reports.templates.manage') ?? false);
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
                Forms\Components\Section::make('Template')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
<<<<<<< HEAD
                            ->maxLength(255),
                        Forms\Components\Select::make('role_id')
                            ->label('Assigned role')
                            ->options(fn (): array => Role::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->helperText('Which organizational role this template is for.')
                            ->required(),
                        Forms\Components\Select::make('frequency')
                            ->options(collect(ReportFrequency::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all())
                            ->default(ReportFrequency::Monthly->value)
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\Textarea::make('description')
                            ->rows(2)
=======
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
>>>>>>> 986d183417c1db2946d5d09abadc57976e55e4ad
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Sections (published as a new version on save)')
                    ->description('Changes never alter submitted reports; a new template version is created instead.')
                    ->schema([
                        Forms\Components\Repeater::make('sections')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(3),
                                Forms\Components\Select::make('data_source')
                                    ->label('Automatic data')
                                    ->options([null => 'Manual narrative'] + ReportDataSources::LABELS)
                                    ->columnSpan(2),
                                Forms\Components\Toggle::make('is_mandatory')
                                    ->label('Mandatory')
                                    ->default(true),
                                Forms\Components\Toggle::make('is_auto')
                                    ->label('Auto-filled')
                                    ->helperText('When off, the author types the content.'),
                                Forms\Components\Textarea::make('help_text')
                                    ->label('Help text for the author')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
<<<<<<< HEAD
                            ->columns(5)
                            ->defaultItems(1)
                            ->reorderable()
                            ->collapsible()
                            ->dehydrated(false)
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('change_note')
                            ->label('Change note (optional)')
                            ->helperText('Recorded on the new version.')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
=======
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
>>>>>>> 986d183417c1db2946d5d09abadc57976e55e4ad
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['role', 'currentVersion'])->withCount(['versions']))
            ->columns([
<<<<<<< HEAD
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('role.name')->label('Role')->badge()->sortable(),
                Tables\Columns\TextColumn::make('frequency')->label('Frequency'),
                Tables\Columns\TextColumn::make('currentVersion.version')->label('Version')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')
                    ->label('Role')
                    ->relationship('role', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('reports.templates.manage') ?? false),
=======
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
>>>>>>> 986d183417c1db2946d5d09abadc57976e55e4ad
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return parent::getEloquentQuery()->whereKey(0);
        }

        return parent::getEloquentQuery()
            ->with(['roles'])
            ->visibleTo($user);
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
            'index' => Pages\ListReportTemplates::route('/'),
            'create' => Pages\CreateReportTemplate::route('/create'),
            'edit' => Pages\EditReportTemplate::route('/{record}/edit'),
        ];
    }
}
