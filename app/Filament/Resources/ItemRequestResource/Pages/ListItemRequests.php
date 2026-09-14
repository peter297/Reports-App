<?php

namespace App\Filament\Resources\ItemRequestResource\Pages;

use App\Filament\Resources\ItemRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListItemRequests extends ListRecords
{
    protected static string $resource = ItemRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $currentUserId = Auth::id();

        return [
            null => Tab::make('All Requests')
                ->query(fn (Builder $query) => $query
                    ->where('user_id', $currentUserId)
                    ->orWhereHas('recipients', fn (Builder $query) => $query->where('user_id', $currentUserId))
                ),

            'Sent Requests' => Tab::make('Sent Requests')
                ->query(fn (Builder $query) => $query->where('user_id', $currentUserId)),

            'Received Requests' => Tab::make('Received Requests')
                ->query(fn (Builder $query) => $query->whereHas('recipients', fn (Builder $query) => $query->where('user_id', $currentUserId)))
        ];
    }
}
