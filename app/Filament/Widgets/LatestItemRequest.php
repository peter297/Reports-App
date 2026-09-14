<?php

namespace App\Filament\Widgets;

use App\Models\ItemRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LatestItemRequest extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ItemRequest::query()
                    ->where('user_id', Auth::id())
                    ->orWhereHas('recipients', fn (Builder $query) => $query->where('user_id', Auth::id()))
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department Name')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('year_session.name')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('term.name')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('week.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estimate_cost')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('remarks')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                // Sender Column
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Sender')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Recipients Column
                Tables\Columns\TextColumn::make('recipients.name') 
                    ->label('Recipients'),
                    // ->formatStateUsing(fn ($state) => $state->pluck('name')->implode(', ')),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            
            ]);
    }
}
