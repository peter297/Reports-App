<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
>>>>>>> 986d183417c1db2946d5d09abadc57976e55e4ad
use Illuminate\Support\Facades\Auth;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

<<<<<<< HEAD
    protected static ?string $navigationGroup = 'School Management';

    protected static ?int $navigationSort = 4;
=======
    protected static ?string $navigationGroup = 'Academic Analysis';

    protected static ?string $navigationLabel = 'Learning Areas';

    protected static ?string $modelLabel = 'Learning Area';

    protected static ?string $pluralModelLabel = 'Learning Areas';
>>>>>>> 986d183417c1db2946d5d09abadc57976e55e4ad

    public static function canViewAny(): bool
    {
        $user = Auth::user();

<<<<<<< HEAD
        return $user?->can('academic_structure.view') ?? false;
=======
        return ($user?->hasUnrestrictedAccess() || $user?->isHead()) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
>>>>>>> 986d183417c1db2946d5d09abadc57976e55e4ad
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
<<<<<<< HEAD
                Forms\Components\Section::make('Subject')
                    ->schema([
                        Forms\Components\Select::make('academic_level_id')
                            ->label('Academic level')
                            ->relationship('academicLevel', 'name', modifyQueryUsing: fn ($query) => $query->orderBy('sort_order'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Early Years Education, Upper Primary or Junior School.'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(table: 'subjects', column: 'name', modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule, Forms\Get $get) => $rule->where('academic_level_id', $get('academic_level_id')), ignoreRecord: true),
                        Forms\Components\TextInput::make('code')
                            ->maxLength(50)
                            ->helperText('Optional short code, e.g. MATH, ENG')
                            ->dehydrateStateUsing(fn (?string $state) => $state ? strtoupper(trim($state)) : null),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
=======
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Mathematics'),
                Forms\Components\Select::make('section')
                    ->options([
                        'EYE' => 'EYE - Early Years Education',
                        'Upper Primary' => 'Upper Primary',
                        'Junior School' => 'Junior School',
                    ])
                    ->required(),
                Forms\Components\Select::make('branch')
                    ->options([
                        'Juja Road' => 'Juja Road',
                        'Kitisuru' => 'Kitisuru',
                        'South C' => 'South C',
                    ])
                    ->helperText('Leave empty to apply to all branches.')
                    ->nullable(),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
            ])->columns(2);
>>>>>>> 986d183417c1db2946d5d09abadc57976e55e4ad
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
<<<<<<< HEAD
                Tables\Columns\TextColumn::make('academicLevel.name')
                    ->label('Level')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('syllabus_mappings_count')
                    ->label('Mappings')
                    ->counts('syllabusMappings')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('academic_level_id')
                    ->label('Academic level')
                    ->relationship('academicLevel', 'name'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('academic_structure.manage') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('academic_structure.manage') ?? false),
                ]),
            ]);
=======
                Tables\Columns\TextColumn::make('section')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch')
                    ->badge()
                    ->placeholder('All branches')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('section')
                    ->options([
                        'EYE' => 'EYE',
                        'Upper Primary' => 'Upper Primary',
                        'Junior School' => 'Junior School',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('section');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->orderBy('section')->orderBy('sort_order')->orderBy('name');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
>>>>>>> 986d183417c1db2946d5d09abadc57976e55e4ad
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubjects::route('/'),
            'create' => Pages\CreateSubject::route('/create'),
            'edit' => Pages\EditSubject::route('/{record}/edit'),
        ];
    }
}
