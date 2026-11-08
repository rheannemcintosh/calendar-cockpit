<?php

namespace App\Http\Controllers;

use App\Enums\FlagIcon;
use App\Http\Requests\StoreFlagRequest;
use App\Http\Requests\UpdateFlagRequest;
use App\Models\Flag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FlagController extends Controller
{
    /**
     * Show the user's flags in the order they are shown on calendar items,
     * and how many calendar items have each one so that deleting it can say
     * how many it will be taken off.
     */
    public function index(Request $request): Response
    {
        $flags = $request->user()->flags()
            ->withCount('calendarItems')
            ->orderBy('id')
            ->get();

        return Inertia::render('flags/Index', [
            'flags' => $flags->map(fn (Flag $flag): array => [
                'id' => $flag->id,
                'name' => $flag->name,
                'icon' => $flag->icon->value,
                'calendarItemsCount' => (int) $flag->calendar_items_count,
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('flags/Create', [
            'icons' => FlagIcon::options(),
        ]);
    }

    public function store(StoreFlagRequest $request): RedirectResponse
    {
        $request->user()->flags()->create($request->flagAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Flag added.')]);

        return to_route('flags.index');
    }

    public function edit(Flag $flag): Response
    {
        return Inertia::render('flags/Edit', [
            'flag' => [
                'id' => $flag->id,
                'name' => $flag->name,
                'icon' => $flag->icon->value,
            ],
            'icons' => FlagIcon::options(),
        ]);
    }

    public function update(UpdateFlagRequest $request, Flag $flag): RedirectResponse
    {
        $flag->update($request->flagAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Flag updated.')]);

        return to_route('flags.index');
    }

    /**
     * Delete the flag and take it off its calendar items, which are kept.
     */
    public function destroy(Flag $flag): RedirectResponse
    {
        $flag->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Flag deleted.')]);

        return to_route('flags.index');
    }
}
