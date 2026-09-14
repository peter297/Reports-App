<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItemRequestResource\Pages;
use App\Models\ItemRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;

class ItemRequestResource extends Resource
{
    protected static ?string $model = ItemRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        $currentUserId = Auth::id();

        return $form->schema([
            Section::make('Request Details')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('department_id')
                            ->relationship(name: 'department', titleAttribute: 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('year_session_id')
                            ->relationship(name: 'year_session', titleAttribute: 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(), // Updates terms dynamically

                        Select::make('term_id')
                            ->relationship(name: 'term', titleAttribute: 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(fn ($get) => \App\Models\Term::where('year_session_id', $get('year_session_id'))
                                ->latest()
                                ->take(3)
                                ->pluck('name', 'id'))
                            ->live(), // Updates weeks dynamically

                        Select::make('week_id')
                            ->relationship(name: 'week', titleAttribute: 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(fn ($get) => \App\Models\Week::where('term_id', $get('term_id'))
                                ->orderBy('id', 'asc')
                                ->pluck('name', 'id')),
                    ]),
                ]),

            Section::make('Items Requested')
                ->schema([
                    Repeater::make('items')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('item_name')
                                    ->label('Item Name')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('estimate_cost')
                                    ->label('Estimated Cost')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Ksh'), // Adds "Ksh" before the input
                            ]),
                        ])
                        ->minItems(1) // At least one item must be added
                        ->collapsible(),
                ]),

            Section::make('Additional Information')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('description')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('remarks')
                            ->required()
                            ->maxLength(255),

                        Hidden::make('user_id')
                            ->default(Auth::id())
                            ->required(),

                        Select::make('recipients')
                            ->relationship('recipients', 'name')
                            ->multiple()
                            ->preload()
                            ->label('Recipients')
                            ->options(fn () => \App\Models\User::where('id', '!=', $currentUserId)
                                ->pluck('name', 'id')),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('department.name')->label('Department')->sortable(),
            Tables\Columns\TextColumn::make('year_session.name')->label('Session')->sortable(),
            Tables\Columns\TextColumn::make('term.name')->label('Term')->sortable(),
            Tables\Columns\TextColumn::make('week.name')->label('Week')->sortable(),
            
            Tables\Columns\TextColumn::make('items')
                ->formatStateUsing(fn ($state) => is_array($state) ? collect($state)->pluck('item_name')->implode(', ') : 'No items')
                ->label('Items'),

            Tables\Columns\TextColumn::make('estimate_cost')
                ->label('Total Cost')
                ->formatStateUsing(fn ($state) => is_array($state) ? 'Ksh ' . collect($state)->sum('estimate_cost') : 'Ksh 0'),

            Tables\Columns\TextColumn::make('description')->label('Description')->searchable(),
            Tables\Columns\TextColumn::make('remarks')->label('Remarks')->searchable(),
            Tables\Columns\TextColumn::make('user.name')->label('Sender')->sortable(),
            Tables\Columns\TextColumn::make('recipients.name')->label('Recipients'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
        ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItemRequests::route('/'),
            'create' => Pages\CreateItemRequest::route('/create'),
            'edit' => Pages\EditItemRequest::route('/{record}/edit'),
        ];
    }
}
