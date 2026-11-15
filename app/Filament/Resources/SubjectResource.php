<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectResource\Pages;
use App\Models\Subject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class SubjectResource extends Resource
{
    protected static ?string $model = Subject::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'School Management';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user?->can('academic_structure.view') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
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
