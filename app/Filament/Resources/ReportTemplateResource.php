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
use Spatie\Permission\Models\Role;

class ReportTemplateResource extends Resource
{
    protected static ?string $model = ReportTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'System Settings';

    protected static ?string $navigationLabel = 'Report Templates';

    protected static ?string $slug = 'report-templates-admin';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'report template';

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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['role', 'currentVersion'])->withCount(['versions']))
            ->columns([
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
            ])
            ->bulkActions([]);
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
