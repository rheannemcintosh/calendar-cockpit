<?php

use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

// The list starts at today, so pin today to Sunday 18 October 2026.
beforeEach(fn () => $this->travelTo('2026-10-18 12:00:00'));

test('guests are sent to the login page from the calendar items list', function () {
    $this->get(route('calendar-items.index'))->assertRedirect(route('login'));
});

test('a user with no calendar items gets an empty list', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar-items/Index')
            ->where('years', [])
            ->where('previousFrom', null));
});

test('calendar items are grouped by year, week and day', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:30')->create(['title' => 'Dentist']);

    $this->actingAs($user)
        ->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar-items/Index')
            ->has('years', 1)
            ->where('years.0.year', 2026)
            ->has('years.0.weeks', 1)
            ->where('years.0.weeks.0.number', 43)
            ->where('years.0.weeks.0.range', '18–24 Oct')
            ->has('years.0.weeks.0.days', 1)
            ->where('years.0.weeks.0.days.0.date', '2026-10-20')
            ->where('years.0.weeks.0.days.0.label', 'Tuesday 20 October')
            ->has('years.0.weeks.0.days.0.items', 1)
            ->where('years.0.weeks.0.days.0.items.0.title', 'Dentist')
            ->where('years.0.weeks.0.days.0.items.0.time', '09:00–10:30'));
});

test('years, weeks, days and calendar items are in date order, earliest first', function () {
    $user = User::factory()->create();
    // Created out of order on purpose.
    CalendarItem::factory()->for($user)->between('2027-02-03 12:00', '2027-02-03 13:00')->create(['title' => 'Next year']);
    CalendarItem::factory()->for($user)->between('2026-10-24 15:00', '2026-10-24 16:00')->create(['title' => 'Saturday']);
    CalendarItem::factory()->for($user)->between('2026-10-18 14:00', '2026-10-18 15:00')->create(['title' => 'Sunday afternoon']);
    CalendarItem::factory()->for($user)->between('2026-10-18 09:00', '2026-10-18 10:00')->create(['title' => 'Sunday morning']);
    CalendarItem::factory()->for($user)->between('2026-11-02 09:00', '2026-11-02 10:00')->create(['title' => 'Later week']);

    $this->actingAs($user)
        ->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('years.0.year', 2026)
            ->where('years.1.year', 2027)
            ->where('years.0.weeks.0.number', 43)
            ->where('years.0.weeks.1.number', 45)
            ->where('years.0.weeks.0.days.0.label', 'Sunday 18 October')
            ->where('years.0.weeks.0.days.0.items.0.title', 'Sunday morning')
            ->where('years.0.weeks.0.days.0.items.1.title', 'Sunday afternoon')
            ->where('years.0.weeks.0.days.1.label', 'Saturday 24 October')
            ->where('years.0.weeks.1.days.0.items.0.title', 'Later week')
            ->where('years.1.weeks.0.days.0.items.0.title', 'Next year'));
});

test('a week that crosses New Year appears once, as week 1 of the new year', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-12-28 09:00', '2026-12-28 10:00')->create(['title' => 'Before New Year']);
    CalendarItem::factory()->for($user)->between('2027-01-01 09:00', '2027-01-01 10:00')->create(['title' => 'New Year’s Day']);

    $this->actingAs($user)
        ->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('years', 1)
            ->where('years.0.year', 2027)
            ->has('years.0.weeks', 1)
            ->where('years.0.weeks.0.number', 1)
            ->where('years.0.weeks.0.range', '27 Dec 2026 – 2 Jan 2027')
            ->where('years.0.weeks.0.days.0.label', 'Monday 28 December')
            ->where('years.0.weeks.0.days.1.label', 'Friday 1 January'));
});

test('users only see their own calendar items', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->create(['title' => 'Mine']);
    CalendarItem::factory()->between('2026-10-20 11:00', '2026-10-20 12:00')->create(['title' => 'Someone else’s']);

    $this->actingAs($user)
        ->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('years.0.weeks.0.days.0.items', 1)
            ->where('years.0.weeks.0.days.0.items.0.title', 'Mine'));
});

test('the list starts at the beginning of today', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-17 23:00', '2026-10-17 23:30')->create(['title' => 'Yesterday']);
    CalendarItem::factory()->for($user)->between('2026-10-18 08:00', '2026-10-18 09:00')->create(['title' => 'Earlier today']);
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->create(['title' => 'Tuesday']);

    $this->actingAs($user)
        ->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('years.0.weeks.0.days', 2)
            ->where('years.0.weeks.0.days.0.items.0.title', 'Earlier today')
            ->where('years.0.weeks.0.days.1.items.0.title', 'Tuesday')
            ->where('previousFrom', '2026-10-17'));
});

test('there is nothing to load when every calendar item is from today onwards', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->create();

    $this->actingAs($user)
        ->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page->where('previousFrom', null));
});

test('loading previous calendar items shows them from the given day', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-01 09:00', '2026-10-01 10:00')->create(['title' => 'Older']);
    CalendarItem::factory()->for($user)->between('2026-10-10 09:00', '2026-10-10 10:00')->create(['title' => 'Last week']);
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->create(['title' => 'Tuesday']);

    $this->actingAs($user)
        ->get(route('calendar-items.index', ['from' => '2026-10-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('years.0.weeks.0.days.0.items.0.title', 'Last week')
            ->where('years.0.weeks.1.days.0.items.0.title', 'Tuesday')
            ->where('previousFrom', '2026-10-01'));
});

test('previous calendar items load about 20 at a time, rounded out to whole days', function () {
    $user = User::factory()->create();

    // 19 earlier days with one calendar item each, then two on the day the 20th falls on.
    foreach (range(1, 19) as $daysAgo) {
        $day = now()->subDays($daysAgo)->toDateString();
        CalendarItem::factory()->for($user)->between("{$day} 09:00", "{$day} 10:00")->create();
    }
    CalendarItem::factory()->for($user)->between('2026-09-28 09:00', '2026-09-28 10:00')->create(['title' => '20th']);
    CalendarItem::factory()->for($user)->between('2026-09-28 08:00', '2026-09-28 08:30')->create(['title' => '21st, same day']);
    CalendarItem::factory()->for($user)->between('2026-09-01 09:00', '2026-09-01 10:00')->create(['title' => 'Much older']);

    $this->actingAs($user)
        ->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page->where('previousFrom', '2026-09-28'));

    $this->actingAs($user)
        ->get(route('calendar-items.index', ['from' => '2026-09-28']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('years.0.weeks.0.days.0.date', '2026-09-28')
            ->has('years.0.weeks.0.days.0.items', 2)
            ->where('previousFrom', '2026-09-01'));
});

test('calendar items show their category and sub-category', function () {
    $user = User::factory()->create();
    $deepWork = SubCategory::factory()
        ->for(Category::factory()->for($user)->create(['name' => 'Work', 'colour' => '#2563EB']))
        ->create(['emoji' => '💻', 'name' => 'Deep work']);
    CalendarItem::factory()->for($user)->for($deepWork)->between('2026-10-20 09:00', '2026-10-20 10:00')->create(['title' => 'Write report']);

    $this->actingAs($user)
        ->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('years.0.weeks.0.days.0.items.0.title', 'Write report')
            ->where('years.0.weeks.0.days.0.items.0.category', ['name' => 'Work', 'colour' => '#2563EB'])
            ->where('years.0.weeks.0.days.0.items.0.subCategory', ['emoji' => '💻', 'name' => 'Deep work']));
});

test('uncategorised calendar items have no category or sub-category', function () {
    $user = User::factory()->create();
    CalendarItem::factory()->for($user)->between('2026-10-20 09:00', '2026-10-20 10:00')->create(['title' => 'Dentist']);

    $this->actingAs($user)
        ->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('years.0.weeks.0.days.0.items.0.title', 'Dentist')
            ->where('years.0.weeks.0.days.0.items.0.time', '09:00–10:00')
            ->where('years.0.weeks.0.days.0.items.0.category', null)
            ->where('years.0.weeks.0.days.0.items.0.subCategory', null));
});

test('calendar items show the latest names, colour and emoji of their category and sub-category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->for($user)->create(['name' => 'Work', 'colour' => '#2563EB']);
    $subCategory = SubCategory::factory()->for($category)->create(['emoji' => '💻', 'name' => 'Deep work']);
    CalendarItem::factory()->for($user)->for($subCategory)->between('2026-10-20 09:00', '2026-10-20 10:00')->create();
    CalendarItem::factory()->for($user)->for($subCategory)->between('2026-10-21 09:00', '2026-10-21 10:00')->create();

    $category->update(['name' => 'Job', 'colour' => '#16A34A']);
    $subCategory->update(['emoji' => '🧠', 'name' => 'Focus']);

    $this->actingAs($user)
        ->get(route('calendar-items.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('years.0.weeks.0.days.0.items.0.category', ['name' => 'Job', 'colour' => '#16A34A'])
            ->where('years.0.weeks.0.days.0.items.0.subCategory', ['emoji' => '🧠', 'name' => 'Focus'])
            ->where('years.0.weeks.0.days.1.items.0.category', ['name' => 'Job', 'colour' => '#16A34A'])
            ->where('years.0.weeks.0.days.1.items.0.subCategory', ['emoji' => '🧠', 'name' => 'Focus']));
});

test('previous calendar items show their category and sub-category', function () {
    $user = User::factory()->create();
    $birthdays = SubCategory::factory()
        ->for(Category::factory()->for($user)->create(['name' => 'Family', 'colour' => '#DB2777']))
        ->create(['emoji' => '🎂', 'name' => 'Birthdays']);
    CalendarItem::factory()->for($user)->for($birthdays)->between('2026-10-10 18:00', '2026-10-10 21:00')->create();

    $this->actingAs($user)
        ->get(route('calendar-items.index', ['from' => '2026-10-10']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('years.0.weeks.0.days.0.items.0.category', ['name' => 'Family', 'colour' => '#DB2777'])
            ->where('years.0.weeks.0.days.0.items.0.subCategory', ['emoji' => '🎂', 'name' => 'Birthdays']));
});

test('categories and sub-categories are loaded with the calendar items, not one at a time', function () {
    $user = User::factory()->create();

    $queriesFor = function (int $count) use ($user): int {
        CalendarItem::query()->delete();

        foreach (range(1, $count) as $daysAhead) {
            $day = now()->addDays($daysAhead)->toDateString();
            $subCategory = SubCategory::factory()->for(Category::factory()->for($user))->create();
            CalendarItem::factory()->for($user)->for($subCategory)->between("{$day} 09:00", "{$day} 10:00")->create();
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($user)->get(route('calendar-items.index'))->assertOk();
        DB::disableQueryLog();

        return count(DB::getQueryLog());
    };

    expect($queriesFor(5))->toBe($queriesFor(1));
});

test('an invalid day to load from is rejected', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.index', ['from' => 'last tuesday']))
        ->assertSessionHasErrors('from');
});
