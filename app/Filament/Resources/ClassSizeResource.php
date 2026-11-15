<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassSizeResource\Pages;
use App\Models\ClassSize;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ClassSizeResource extends Resource
{
    protected static ?string $model = ClassSize::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    protected static ?string $navigationGroup = 'Attendance';

    protected static ?string $navigationLabel = 'Class Sizes';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Class Size';

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user?->can('attendance.view') ?? false;
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user?->can('attendances.manage') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        return $user?->can('attendances.manage') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        return $user?->can('attendances.manage') ?? false;
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
                    ->options(function (): array {
                        $user = Auth::user();
                        $branches = $user?->assignableBranchNames() ?? [];

                        return array_combine($branches, $branches);
                    })
                    ->default(function (): ?string {
                        $user = Auth::user();

                        if ($user?->hasUnrestrictedAccess()) {
                            return $user?->branch?->name;
                        }

                        $branches = $user?->assignableBranchNames() ?? [];

                        return count($branches) === 1 ? $branches[0] : null;
                    })
                    ->disabled(function (): bool {
                        $user = Auth::user();

                        if ($user?->hasUnrestrictedAccess()) {
                            return false;
                        }

                        return count($user?->assignableBranchNames() ?? []) <= 1;
                    })
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set): void {
                        $set('section', null);
                        $set('class_id', null);
                        $set('stream_id', null);
                    })
                    ->required()
                    ->dehydrated(),
                Forms\Components\Select::make('section')
                    ->options(fn (Forms\Get $get): array => Auth::user()?->assignableSectionCodes($get('branch')) ?? [])
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set): void {
                        $set('class_id', null);
                        $set('stream_id', null);
                    })
                    ->required(),
                Forms\Components\Select::make('class_id')
                    ->label('Class')
                    ->relationship(name: 'class', titleAttribute: 'name')
                    ->options(fn (Forms\Get $get): array => AttendanceResource::attendanceClassOptions($get('branch'), $get('section')))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set): mixed => $set('stream_id', null))
                    ->required(),
                Forms\Components\Select::make('stream_id')
                    ->label('Stream')
                    ->relationship(name: 'stream', titleAttribute: 'name')
                    ->options(fn (Forms\Get $get): array => AttendanceResource::attendanceStreamOptions(
                        $get('branch'),
                        $get('section'),
                        $get('class_id') ? (int) $get('class_id') : null,
                    ))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->rules([
                        function (Forms\Get $get, ?Model $record): \Closure {
                            return function (string $attribute, mixed $value, \Closure $fail) use ($get, $record): void {
                                if (! $value || ! $get('branch') || ! $get('section') || ! $get('class_id')) {
                                    return;
                                }

                                $exists = ClassSize::query()
                                    ->where('branch', $get('branch'))
                                    ->where('section', $get('section'))
                                    ->where('class_id', $get('class_id'))
                                    ->where('stream_id', $value)
                                    ->when($record, fn (Builder $query): Builder => $query->whereKeyNot($record->getKey()))
                                    ->exists();

                                if ($exists) {
                                    $fail('A size record already exists for this class and stream.');
                                }
                            };
                        },
                    ]),
                Forms\Components\TextInput::make('total_boys')
                    ->label('Total Boys')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('total_girls')
                    ->label('Total Girls')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('branch')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('section')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('class.name')
                    ->label('Class')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('stream.name')
                    ->label('Stream')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_boys')
                    ->label('Boys')
                    ->numeric()
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Boys total')),
                Tables\Columns\TextColumn::make('total_girls')
                    ->label('Girls')
                    ->numeric()
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Girls total')),
                Tables\Columns\TextColumn::make('class_total')
                    ->label('Total')
                    ->numeric()
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('All classes')),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch')
                    ->options([
                        'Juja Road' => 'Juja Road',
                        'Kitisuru' => 'Kitisuru',
                        'South C' => 'South C',
                    ]),
                Tables\Filters\SelectFilter::make('section')
                    ->options([
                        'EYE' => 'EYE - Early Years Education',
                        'Upper Primary' => 'Upper Primary',
                        'Junior School' => 'Junior School',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('attendances.manage') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('attendances.manage') ?? false),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return parent::getEloquentQuery()->whereKey(0);
        }

        return parent::getEloquentQuery()
            ->with(['class', 'stream'])
            ->accessibleTo($user);
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
            'index' => Pages\ListClassSizes::route('/'),
            'create' => Pages\CreateClassSize::route('/create'),
            'edit' => Pages\EditClassSize::route('/{record}/edit'),
        ];
    }
}
