<?php

namespace App\Http\Controllers;

use App\Support\CalendarItemTimeline;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarItemController extends Controller
{
    /**
     * Show the user's calendar items, grouped by year, week and day.
     */
    public function index(Request $request): Response
    {
        $calendarItems = $request->user()->calendarItems()
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        return Inertia::render('calendar-items/Index', [
            'years' => CalendarItemTimeline::group($calendarItems),
        ]);
    }
}
