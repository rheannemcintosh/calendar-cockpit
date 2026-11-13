<?php

namespace App\Http\Controllers;

use App\Enums\Firmness;
use App\Enums\TimeType;
use App\Http\Requests\StoreCalendarItemRequest;
use App\Models\Category;
use App\Models\SubCategory;
use App\Support\CalendarItemDay;
use App\Support\CalendarItemMonth;
use App\Support\CalendarItemTimeline;
use App\Support\CalendarItemWeek;
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
     * Show the user's calendar items from today, in their timezone, onwards, or from an earlier
     * day, such as the one open in the day view or once they have loaded
     * previous ones, grouped by year, week and day.
     */
    public function index(Request $request): Response
    {
        $request->validate(['from' => ['nullable', 'date_format:Y-m-d']]);

        $from = $request->filled('from')
            ? CarbonImmutable::createFromFormat('!Y-m-d', $request->string('from')->toString())
            : $request->user()->today();

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
            'from' => $request->filled('from') ? [
                'date' => $from->toDateString(),
                'label' => $from->format('l j F Y'),
            ] : null,
            'previousFrom' => $earliestPrevious?->toDateString(),
            'timeTypes' => TimeType::options(),
            'firmnesses' => Firmness::options(),
        ]);
    }

    /**
     * Show the user's calendar items on one day, today unless another day is
     * given, placed on a timeline of the day to the minute.
     */
    public function day(Request $request, ?string $date = null): Response
    {
        $day = $date === null ? $request->user()->today() : self::dayFrom($date);

        $calendarItems = $request->user()->calendarItems()
            ->where('starts_at', '<', $day->addDay())
            ->where('ends_at', '>', $day)
            ->with('subCategory.category')
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        return Inertia::render('calendar-items/Day', [
            'date' => $day->toDateString(),
            'label' => $day->format('l j F Y'),
            'previousDate' => $day->subDay()->toDateString(),
            'nextDate' => $day->addDay()->toDateString(),
            'items' => CalendarItemDay::place($calendarItems, $day),
            'timeTypes' => TimeType::options(),
            'firmnesses' => Firmness::options(),
        ]);
    }

    /**
     * Show the user's calendar items in one week, Sunday to Saturday, placed on
     * a timeline of each day to the minute. The week is this week unless a day
     * in another week is given, and that day is kept so other views open on it.
     */
    public function week(Request $request, ?string $date = null): Response
    {
        $day = $date === null ? $request->user()->today() : self::dayFrom($date);
        $weekStart = CarbonImmutable::instance(CalendarItemTimeline::weekStart($day));
        $weekEnd = $weekStart->addDays(6);

        $calendarItems = $request->user()->calendarItems()
            ->where('starts_at', '<', $weekEnd->addDay())
            ->where('ends_at', '>', $weekStart)
            ->with('subCategory.category')
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        $range = CalendarItemTimeline::weekRange($weekStart);

        return Inertia::render('calendar-items/Week', [
            'date' => $day->toDateString(),
            'label' => $weekStart->year === $weekEnd->year ? $range.' '.$weekEnd->year : $range,
            'weekNumber' => CalendarItemTimeline::weekNumber($weekStart),
            'previousDate' => $day->subWeek()->toDateString(),
            'nextDate' => $day->addWeek()->toDateString(),
            'days' => CalendarItemWeek::days($calendarItems, $weekStart),
            'timeTypes' => TimeType::options(),
            'firmnesses' => Firmness::options(),
        ]);
    }

    /**
     * Show the user's calendar items in one month, on a grid of its weeks from
     * Sunday to Saturday, each day with its calendar items in start order. The
     * month is this month unless a month (YYYY-MM) or a day in another month
     * (YYYY-MM-DD) is given. A day given is kept so other views open on it,
     * and a month alone opens on its 1st.
     */
    public function month(Request $request, ?string $date = null): Response
    {
        $day = match (true) {
            $date === null => $request->user()->today(),
            strlen($date) === 7 => self::monthFrom($date),
            default => self::dayFrom($date),
        };

        $gridStart = CalendarItemMonth::gridStart($day);
        $gridEnd = CalendarItemMonth::gridEnd($day);

        $calendarItems = $request->user()->calendarItems()
            ->where('starts_at', '<', $gridEnd->addDay())
            ->where('ends_at', '>', $gridStart)
            ->with('subCategory.category')
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        return Inertia::render('calendar-items/Month', [
            'date' => $day->toDateString(),
            'month' => $day->format('Y-m'),
            'label' => $day->format('F Y'),
            'previousDate' => $day->subMonthNoOverflow()->toDateString(),
            'nextDate' => $day->addMonthNoOverflow()->toDateString(),
            'weeks' => CalendarItemMonth::weeks($calendarItems, $day),
            'timeTypes' => TimeType::options(),
        ]);
    }

    /**
     * Show the form with the user's categories that have sub-categories, and
     * the sub-categories in each one with its default time type, both in
     * alphabetical order, every time type and firmness to choose from, and
     * whether a location can be searched for, which needs a Mapbox token.
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
            'firmnesses' => Firmness::options(),
            'locationSearch' => filled(config('services.mapbox.token')),
        ]);
    }

    public function store(StoreCalendarItemRequest $request): RedirectResponse
    {
        $request->user()->calendarItems()->create($request->calendarItemAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Calendar item created.')]);

        return to_route('calendar-items.index');
    }

    /**
     * The day a YYYY-MM-DD date names, or not found when it is not a real day.
     */
    private static function dayFrom(string $date): CarbonImmutable
    {
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date);

        abort_if($day === null || $day->toDateString() !== $date, 404);

        return $day;
    }

    /**
     * The 1st of the month a YYYY-MM month names, or not found when it is not a real month.
     */
    private static function monthFrom(string $month): CarbonImmutable
    {
        $day = CarbonImmutable::createFromFormat('!Y-m', $month);

        abort_if($day === null || $day->format('Y-m') !== $month, 404);

        return $day;
    }
}
