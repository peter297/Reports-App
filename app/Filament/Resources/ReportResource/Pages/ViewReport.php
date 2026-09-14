<?php
namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Models\Report;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\ViewEntry;

class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    public function getTitle(): string | Htmlable
    {
        /** @var Post */
        $record = $this->getRecord();

        return $record->subject;
    }

    protected function getActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Download PDF')
                ->url(fn (Report $record): string => route('reports.pdf', $record))
                ->openUrlInNewTab(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                ViewEntry::make('pdf_preview')
                    ->label('PDF Preview')
                    ->columnSpanFull()
                    ->view('filament.infolists.entries.report-pdf-preview'),
            ]);
    }
}
