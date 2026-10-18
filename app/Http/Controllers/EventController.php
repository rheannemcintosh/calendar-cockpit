<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Support\EventTimeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * Show the user's events, grouped by year, week and day.
     */
    public function index(Request $request): Response
    {
        $events = $request->user()->events()
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        return Inertia::render('events/Index', [
            'years' => EventTimeline::group($events),
        ]);
    }

    /**
     * Show the form for creating an event.
     */
    public function create(): Response
    {
        return Inertia::render('events/Create');
    }

    /**
     * Store a new event for the user.
     */
    public function store(StoreEventRequest $request): RedirectResponse
    {
        $request->user()->events()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Event created.')]);

        return to_route('events.index');
    }
}
