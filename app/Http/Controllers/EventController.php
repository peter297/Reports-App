<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function generatePDF()
    {
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



