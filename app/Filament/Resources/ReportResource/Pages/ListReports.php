<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListReports extends ListRecords
{
    protected static string $resource = ReportResource::class;

    protected function getTableQuery(): Builder
    {
        $user = Auth::user();

        return parent::getTableQuery()->accessibleTo($user);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            null => Tab::make('All')
                ->query(function (Builder $query): Builder {
                    if (Auth::user()->hasUnrestrictedAccess()) {
                        return $query;
                    }

                    return $query->where(function (Builder $query): void {
                        $query->where('reports.user_id', Auth::id())
                            ->orWhereHas('user', fn (Builder $query) => $query->where('line_manager_id', Auth::id()));
                    });
                }),

            'Sent Reports' => Tab::make('Sent Reports')
                ->query(fn (Builder $query) => $query->where('user_id', Auth::id())),

            'Received Reports' => Tab::make('Received Reports')
                ->query(fn (Builder $query): Builder => $query->whereHas(
                    'user',
                    fn (Builder $query): Builder => $query->where('line_manager_id', Auth::id()),
                )),
        ];
    }

    protected function getActions(): array
    {
        $actions = parent::getHeaderActions();

        // Check the current tab and modify actions accordingly
        if ($this->getCurrentTab() === 'Received Reports') {
            // Remove the Edit action or disable it
            return array_filter($actions, fn ($action) => !($action instanceof Actions\EditAction));
        }

        return $actions;
    }
}
