<?php

use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

const VALID_CALENDAR_ITEM = [
    'title' => 'Dentist',
    'date' => '2026-10-20',
    'start_time' => '09:00:00',
    'end_time' => '10:30:00',
];

test('guests are sent to the login page from the create calendar item form', function () {
    $this->get(route('calendar-items.create'))->assertRedirect(route('login'));
});

test('guests cannot create a calendar item', function () {
    $this->post(route('calendar-items.store'), VALID_CALENDAR_ITEM)
        ->assertRedirect(route('login'));

    expect(CalendarItem::count())->toBe(0);
});

test('signed-in users see the create calendar item form', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('calendar-items/Create'));
});

test('the form lists the categories with sub-categories and their sub-categories in alphabetical order', function () {
    $user = User::factory()->create();
    $work = Category::factory()->for($user)->create(['name' => 'Work', 'colour' => '#3B82F6']);
    $family = Category::factory()->for($user)->create(['name' => 'family', 'colour' => '#EF4444']);
    Category::factory()->for($user)->create(['name' => 'Empty']);
    $meetings = SubCategory::factory()->for($work)->create(['emoji' => '📅', 'name' => 'Meetings']);
    $deepWork = SubCategory::factory()->for($work)->create(['emoji' => '💻', 'name' => 'Deep work']);
    $birthdays = SubCategory::factory()->for($family)->create(['emoji' => '🎂', 'name' => 'Birthdays']);

    $this->actingAs($user)
        ->get(route('calendar-items.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar-items/Create')
            ->where('categories', [
                [
                    'id' => $family->id,
                    'name' => 'family',
                    'colour' => '#EF4444',
                    'subCategories' => [
                        ['id' => $birthdays->id, 'emoji' => '🎂', 'name' => 'Birthdays'],
                    ],
                ],
                [
                    'id' => $work->id,
                    'name' => 'Work',
                    'colour' => '#3B82F6',
                    'subCategories' => [
                        ['id' => $deepWork->id, 'emoji' => '💻', 'name' => 'Deep work'],
                        ['id' => $meetings->id, 'emoji' => '📅', 'name' => 'Meetings'],
                    ],
                ],
            ]));
});

test('the form only lists the user\'s own categories', function () {
    SubCategory::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('categories', []));
});

test('a user can create a calendar item with a title, date, start time and end time', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('calendar-items.store'), VALID_CALENDAR_ITEM)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('calendar-items.index'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Calendar item created.']);

    $calendarItem = CalendarItem::sole();

    expect($calendarItem->user_id)->toBe($user->id)
        ->and($calendarItem->title)->toBe('Dentist')
        ->and($calendarItem->starts_at->toDateTimeString())->toBe('2026-10-20 09:00:00')
        ->and($calendarItem->ends_at->toDateTimeString())->toBe('2026-10-20 10:30:00');
});

test('a calendar item is uncategorised when no sub-category is chosen', function (?string $subCategoryId) {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...VALID_CALENDAR_ITEM, 'sub_category_id' => $subCategoryId])
        ->assertSessionHasNoErrors();

    expect(CalendarItem::sole()->sub_category_id)->toBeNull();
})->with([
    'empty' => '',
    'null' => null,
]);

test('a user can create a calendar item with one of their sub-categories', function () {
    $user = User::factory()->create();
    $subCategory = SubCategory::factory()->for(Category::factory()->for($user))->create();

    $this->actingAs($user)
        ->post(route('calendar-items.store'), [...VALID_CALENDAR_ITEM, 'sub_category_id' => $subCategory->id])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('calendar-items.index'));

    $calendarItem = CalendarItem::sole();

    expect($calendarItem->sub_category_id)->toBe($subCategory->id)
        ->and($calendarItem->subCategory->category_id)->toBe($subCategory->category_id);
});

test('a calendar item cannot be given a sub-category that is not the user\'s', function (string $case) {
    $user = User::factory()->create();

    $subCategoryId = match ($case) {
        'another user\'s' => SubCategory::factory()->create()->id,
        'deleted' => tap(SubCategory::factory()->for(Category::factory()->for($user))->create())->delete()->id,
        'not a number' => 'work',
    };

    $this->actingAs($user)
        ->post(route('calendar-items.store'), [...VALID_CALENDAR_ITEM, 'sub_category_id' => $subCategoryId])
        ->assertSessionHasErrors(['sub_category_id' => 'Please choose a category again.']);

    expect(CalendarItem::count())->toBe(0);
})->with(['another user\'s', 'deleted', 'not a number']);

test('times can be sent with or without seconds', function (string $startTime, string $endTime) {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...VALID_CALENDAR_ITEM, 'start_time' => $startTime, 'end_time' => $endTime])
        ->assertSessionHasNoErrors();

    expect(CalendarItem::sole()->starts_at->toDateTimeString())->toBe('2026-10-20 09:15:00');
})->with([
    'with seconds' => ['09:15:00', '10:00:00'],
    'without seconds' => ['09:15', '10:00'],
]);

test('a calendar item needs a title', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...VALID_CALENDAR_ITEM, 'title' => ''])
        ->assertSessionHasErrors(['title' => 'Please enter a title.']);

    expect(CalendarItem::count())->toBe(0);
});

test('a calendar item title cannot be longer than 255 characters', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...VALID_CALENDAR_ITEM, 'title' => str_repeat('a', 256)])
        ->assertSessionHasErrors('title');

    expect(CalendarItem::count())->toBe(0);
});

test('a calendar item needs a date, a start time and an end time', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), ['title' => 'Dentist'])
        ->assertSessionHasErrors([
            'date' => 'Please choose a date.',
            'start_time' => 'Please choose a start time.',
            'end_time' => 'Please choose an end time.',
        ]);

    expect(CalendarItem::count())->toBe(0);
});

test('the end time must be after the start time', function (string $endTime) {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...VALID_CALENDAR_ITEM, 'end_time' => $endTime])
        ->assertSessionHasErrors(['end_time' => 'The end time must be after the start time.']);

    expect(CalendarItem::count())->toBe(0);
})->with([
    'the same time' => '09:00:00',
    'earlier' => '08:59:00',
]);

test('a calendar item cannot be given a date and time in one field to span several days', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('calendar-items.store'), [...VALID_CALENDAR_ITEM, 'date' => '2026-10-20T09:00', 'end_time' => '2026-10-22 17:00'])
        ->assertSessionHasErrors(['date', 'end_time']);

    expect(CalendarItem::count())->toBe(0);
});
