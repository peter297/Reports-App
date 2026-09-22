<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventController extends Controller
{
    public function previewCalendar(Event $event): View
    {
        Gate::authorize('view', $event);

        abort_unless($event->calendar_path && Storage::disk('public')->exists($event->calendar_path), 404);

        $mime = (string) Storage::disk('public')->mimeType($event->calendar_path);

        return view('events.calendar-preview', [
            'event' => $event,
            'url' => Storage::disk('public')->url($event->calendar_path),
            'isPdf' => str_contains($mime, 'pdf'),
        ]);
    }

    public function downloadUploadedCalendar(): StreamedResponse
    {
        abort_unless(auth()->user()?->can('view_any_event'), 403);

        $event = Event::query()
            ->whereNotNull('calendar_path')
            ->latest('updated_at')
            ->first();

        abort_unless($event && Storage::disk('public')->exists($event->calendar_path), 404);

        $filename = preg_replace('/[^A-Za-z0-9\-_ ]/', '', $event->name).'-calendar.'.pathinfo($event->calendar_path, PATHINFO_EXTENSION);

        return Storage::disk('public')->download($event->calendar_path, $filename);
    }

    public function calendarPdf(int $year, int $month, Request $request): Response
    {
        abort_unless(auth()->user()?->can('view_any_event'), 403);
        abort_unless($year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12, 404);

        $branch = $request->query('branch');

        $monthStart = Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $gridStart = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SATURDAY);

        $events = Event::query()
            ->whereDate('event_date', '<=', $monthEnd->toDateString())
            ->where(function ($query) use ($monthStart): void {
                $query->whereDate('end_date', '>=', $monthStart->toDateString())
                    ->orWhere(function ($query) use ($monthStart): void {
                        $query->whereNull('end_date')
                            ->whereDate('event_date', '>=', $monthStart->toDateString());
                    });
            })
            ->when($branch && $branch !== 'all', fn ($query) => $query->where('branch', $branch))
            ->orderBy('event_date')
            ->get();

        $weeks = [];
        $cursor = $gridStart->copy();

        while ($cursor <= $gridEnd) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $day = $cursor->copy()->toDateString();

                $week[] = [
                    'date' => $cursor->copy(),
                    'in_month' => $cursor->month === $month,
                    'events' => $events->filter(
                        fn (Event $event): bool => $day >= substr((string) $event->event_date, 0, 10)
                            && $day <= substr((string) ($event->end_date ?? $event->event_date), 0, 10)
                    )->values(),
                ];

                $cursor->addDay();
            }

            $weeks[] = $week;
        }

        $branchColors = [
            'All Branches' => '#7c3aed',
            'Juja Rd' => '#f59e0b',
            'Juja Road' => '#f59e0b',
            'Kitisuru' => '#3b82f6',
            'South C' => '#10b981',
        ];

        $quoteImagePath = null;
        $quote = \App\Models\MonthQuote::query()
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($quote?->image_path && Storage::disk('public')->exists($quote->image_path)) {
            $quoteImagePath = Storage::disk('public')->path($quote->image_path);
        }

        return Pdf::loadView('pdf.events-calendar', [
            'monthLabel' => $monthStart->format('F Y'),
            'weeks' => $weeks,
            'branch' => $branch && $branch !== 'all' ? $branch : null,
            'branchColors' => $branchColors,
            'quoteImagePath' => $quoteImagePath,
        ])
            ->setPaper('a4', 'landscape')
            ->stream("calendar-of-events-{$year}-".str_pad((string) $month, 2, '0', STR_PAD_LEFT).'.pdf');
    }

    public function generatePDF()
    {
        abort_unless(auth()->user()?->can('view_any_event'), 403);
        $events = Event::with(['term', 'week', 'classes']) // Load relationships
            ->get()
            ->sort(function ($a, $b) {
                $termOrder = ['Term 1' => 1, 'Term 2' => 2, 'Term 3' => 3];

                $termA = $termOrder[$a->term->name ?? ''] ?? 999;
                $termB = $termOrder[$b->term->name ?? ''] ?? 999;

                if ($termA === $termB) {
                    return strtotime($a->event_date) - strtotime($b->event_date);
                }

                return $termA - $termB;
            });

        $pdf = Pdf::loadView('pdf.events', compact('events'))->setPaper('a4', 'landscape');

        return $pdf->stream('events.pdf');
    }
}
