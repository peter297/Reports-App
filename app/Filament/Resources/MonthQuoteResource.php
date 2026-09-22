<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MonthQuoteResource\Pages;
use App\Models\MonthQuote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MonthQuoteResource extends Resource
{
    protected static ?string $model = MonthQuote::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Academic Management';

    protected static ?string $navigationLabel = 'Month Quotes';

    protected static ?string $modelLabel = 'Month Quote';

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return ($user?->hasUnrestrictedAccess() || $user?->can('view_any_month::quote')) ?? false;
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return ($user?->hasUnrestrictedAccess() || $user?->can('create_month::quote')) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        return ($user?->hasUnrestrictedAccess() || $user?->can('update_month::quote')) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        return ($user?->hasUnrestrictedAccess() || $user?->can('delete_month::quote')) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('month')
                    ->options([
                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
                    ])
                    ->required()
                    ->unique(
                        table: MonthQuote::class,
                        column: 'month',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule, Forms\Get $get): \Illuminate\Validation\Rules\Unique => $rule->where('year', $get('year')),
                    ),
                Forms\Components\TextInput::make('year')
                    ->numeric()
                    ->minValue(2000)
                    ->maxValue(2100)
                    ->default(now()->year)
                    ->required(),
                Forms\Components\FileUpload::make('image_path')
                    ->label('Quotes Image')
                    ->helperText('Shown as the sidebar on the generated monthly calendar, like the Barakah panel.')
                    ->disk('public')
                    ->directory('month-quotes')
                    ->image()
                    ->imageEditor()
                    ->maxSize(10240)
                    ->openable()
                    ->downloadable()
                    ->previewable()
                    ->required()
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('year')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('month_name')
                    ->label('Month')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('month', $direction))
                    ->searchable(),
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Quotes Image')
                    ->disk('public')
                    ->square(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('year', 'desc');
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
            'index' => Pages\ListMonthQuotes::route('/'),
            'create' => Pages\CreateMonthQuote::route('/create'),
            'edit' => Pages\EditMonthQuote::route('/{record}/edit'),
        ];
    }
}
