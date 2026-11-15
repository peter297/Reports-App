<?php

namespace App\Filament\Resources\EventResource\Pages;

use App\Filament\Resources\EventResource;
use App\Models\MonthQuote;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithFormActions;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class MonthQuotes extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = EventResource::class;

    protected static string $view = 'filament.resources.event-resource.pages.month-quotes';

    protected static ?string $title = 'Month Quotes';

    public function getBreadcrumbs(): array
    {
        return ['Events', 'Month Quotes'];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(MonthQuote::query())
            ->defaultSort('year', 'desc')
            ->defaultSort('month', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('year')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('month_name')
                    ->label('Month')
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('month', $direction))
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->model(MonthQuote::class)
                    ->label('Add month quote')
                    ->form(static::quoteForm())
                    ->mutateFormDataUsing(fn (array $data) => $data)
                    ->successNotificationTitle('Month quote added'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form(static::quoteForm())
                    ->successNotificationTitle('Month quote updated'),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('events.manage') ?? false),
            ])
            ->emptyStateHeading('No month quotes yet')
            ->emptyStateDescription('Add a quote image for the monthly calendar sidebar. Only one image per month/year.');
    }

    /** @return array<int, Forms\Components\Component> */
    public static function quoteForm(): array
    {
        return [
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
                    modifyRuleUsing: fn (Unique $rule, Forms\Get $get): Unique => $rule->where('year', $get('year')),
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
        ];
    }

    /**
     * Month quotes are only for the super administrator (they configure branding).
     */
    public static function canAccess(array $parameters = []): bool
    {
        return (bool) auth()->user()?->hasSystemAccess();
    }
}
