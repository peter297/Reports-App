<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;

class LatestReports extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Report::query()
                    ->accessibleTo(Auth::user())
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('category')
                ->sortable()
                ->searchable()
                ->label('Category'),
        
            TextColumn::make('subject')
                ->sortable()
                ->searchable()
                ->label('Subject'),
        
            TextColumn::make('user.name')
                ->label('Sender')
                ->sortable()
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
        
            TextColumn::make('recipients.name')
                ->label('Recipients')
                ->toggleable(isToggledHiddenByDefault: true),
        
            TextColumn::make('created_at')
                ->label('Created Date')
                ->sortable()
                ->color('success')
                ->badge()
                ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('M d, Y'))
                ->toggleable(isToggledHiddenByDefault: true),
        
            TextColumn::make('updated_at')
                ->label('Updated Date')
                ->sortable()
                ->color('success')
                ->badge()
                ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('M d, Y'))
                ->toggleable(isToggledHiddenByDefault: true),
        
            TextColumn::make('file_paths')
                ->formatStateUsing(function (Report $record) {
                    return collect($record->file_paths)->map(function ($path) {
                        $filename = basename($path);
                        // Remove the random numbers before the filename using regex
                        $cleanFilename = preg_replace('/^\d+/', '', $filename);
                        $cleanFilename = ltrim($cleanFilename, '_ '); // Remove any leading underscores or spaces
                        $url = asset('storage/attachments/' . $filename);
                        return "<a href=\"{$url}\" target=\"_blank\" class=\" bg-blue-500 rounded px-2 py-1 mb-1 mr-1 text-sm hover:underline\">{$cleanFilename}</a>";
                    })->implode(''); // Join badges without additional separators
                })
                ->label('Attachments')
                ->html(),
            ]);
    }
}
