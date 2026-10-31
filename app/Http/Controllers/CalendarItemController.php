<?php

namespace App\Http\Controllers;

use App\Enums\TimeType;
use App\Http\Requests\StoreCalendarItemRequest;
use App\Models\Category;
use App\Models\SubCategory;
use App\Support\CalendarItemTimeline;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarItemController extends Controller
{
    /**
     * Roughly how many earlier calendar items each "Load previous" adds.
     * It is rounded out to whole days, so a day is never half shown.
     */
    private const PREVIOUS_STEP = 20;

    /**
     * Show the user's calendar items from today onwards, or from an earlier
     * day once they have loaded previous ones, grouped by year, week and day.
     */
    public function index(Request $request): Response
    {
        $request->validate(['from' => ['nullable', 'date_format:Y-m-d']]);

        $from = $request->filled('from')
            ? CarbonImmutable::createFromFormat('!Y-m-d', $request->string('from')->toString())
            : CarbonImmutable::today();

        $calendarItems = $request->user()->calendarItems()
            ->where('starts_at', '>=', $from)
            ->with('subCategory.category')
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        $previous = fn () => $request->user()->calendarItems()->where('starts_at', '<', $from);

        $earliestPrevious = $previous()->orderByDesc('starts_at')->skip(self::PREVIOUS_STEP - 1)->first()->starts_at
            ?? $previous()->orderBy('starts_at')->first()?->starts_at;

        return Inertia::render('calendar-items/Index', [
            'years' => CalendarItemTimeline::group($calendarItems),
            'previousFrom' => $earliestPrevious?->toDateString(),
            'timeTypes' => TimeType::options(),
        ]);
    }

    /**
     * Show the form with the user's categories that have sub-categories, and
     * the sub-categories in each one with its default time type, both in
     * alphabetical order, and every time type to choose from.
     */
    public function create(Request $request): Response
    {
        $categories = $request->user()->categories()
            ->whereHas('subCategories')
            ->with(['subCategories' => fn (HasMany $query) => $query->orderByRaw('lower(name)')->orderBy('id')])
            ->orderByRaw('lower(name)')
            ->orderBy('id')
            ->get();

        return Inertia::render('calendar-items/Create', [
            'categories' => $categories->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'colour' => $category->colour,
                'subCategories' => $category->subCategories->map(fn (SubCategory $subCategory): array => [
                    'id' => $subCategory->id,
                    'emoji' => $subCategory->emoji,
                    'name' => $subCategory->name,
                    'defaultTimeType' => $subCategory->default_time_type->value,
                ]),
            ]),
            'timeTypes' => TimeType::options(),
        ]);
    }

    public function store(StoreCalendarItemRequest $request): RedirectResponse
    {
        $request->user()->calendarItems()->create($request->calendarItemAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Calendar item created.')]);

        return to_route('calendar-items.index');
    }
}
