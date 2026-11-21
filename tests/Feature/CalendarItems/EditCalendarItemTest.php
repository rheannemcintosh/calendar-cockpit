<?php

use App\Enums\Firmness;
use App\Enums\TimeType;
use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(fn () => $this->travelTo('2026-10-20 12:00:00'));

const UPDATED_CALENDAR_ITEM = [
    'title' => 'Dentist check-up',
    'date' => '2026-10-22',
    'start_time' => '14:00',
    'end_time' => '15:15',
    'time_type' => 'window',
    'firmness' => 'tentative',
];

function dentist(User $user): CalendarItem
{
    return CalendarItem::factory()->for($user)->between('2026-10-21 09:00', '2026-10-21 10:30')->create(['title' => 'Dentist']);
}

test('guests are sent to the login page from the edit calendar item form', function () {
    $this->get(route('calendar-items.edit', dentist(User::factory()->create())))->assertRedirect(route('login'));
});

test('guests cannot change a calendar item', function () {
    $calendarItem = dentist(User::factory()->create());

    $this->put(route('calendar-items.update', $calendarItem), UPDATED_CALENDAR_ITEM)->assertRedirect(route('login'));

    expect($calendarItem->fresh()->title)->toBe('Dentist');
});

test('the edit form is filled in with every one of the calendar item\'s current details', function () {
    $user = User::factory()->create();
    $subCategory = SubCategory::factory()->for(Category::factory()->for($user))->create(['default_time_type' => TimeType::Window]);
    [$prePaid, , $important] = $user->flags()->orderBy('id')->get()->all();
    $calendarItem = CalendarItem::factory()->for($user)->for($subCategory)->between('2026-10-21 09:00', '2026-10-21 10:30')->create([
        'title' => 'Dentist',
        'time_type' => TimeType::Block,
        'time_type_overridden' => true,
        'firmness' => Firmness::Idea,
        'location_name' => 'Smile Dental Practice',
        'location_address' => '12 High Street',
        'location_latitude' => 55.95,
        'location_longitude' => -3.18,
    ]);
    $calendarItem->flags()->attach([$important->id, $prePaid->id]);

    $this->actingAs($user)
        ->get(route('calendar-items.edit', $calendarItem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar-items/Edit')
            ->where('calendarItem', [
                'id' => $calendarItem->id,
                'title' => 'Dentist',
                'allDay' => false,
                'date' => '2026-10-21',
                'endDate' => '2026-10-21',
                'startTime' => '09:00',
                'endTime' => '10:30',
                'subCategoryId' => $subCategory->id,
                'timeType' => 'block',
                'timeTypeOverridden' => true,
                'firmness' => 'idea',
                'flagIds' => [$prePaid->id, $important->id],
                'location' => [
                    'name' => 'Smile Dental Practice',
                    'address' => '12 High Street',
                    'latitude' => 55.95,
                    'longitude' => -3.18,
                ],
            ])
            ->where('view', 'list')
            ->where('backUrl', route('calendar-items.index'))
            ->has('categories', 1)
            ->where('timeTypes', TimeType::options())
            ->where('firmnesses', Firmness::options())
            ->has('flags', 3)
            ->has('locationSearch'));
});

test('the edit form is filled in with an uncategorised calendar item with no flags or location', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('calendar-items.edit', dentist($user)))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('calendarItem.subCategoryId', null)
            ->where('calendarItem.flagIds', [])
            ->where('calendarItem.location', null));
});

test('the edit form is filled in with an all-day calendar item\'s last day and no times', function () {
    $user = User::factory()->create();
    $subCategory = SubCategory::factory()->for(Category::factory()->for($user))->create(['default_time_type' => TimeType::Block]);
    $calendarItem = CalendarItem::factory()->for($user)->for($subCategory)->allDay('2026-10-21', '2026-10-23')->create();

    $this->actingAs($user)
        ->get(route('calendar-items.edit', $calendarItem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('calendarItem.allDay', true)
            ->where('calendarItem.date', '2026-10-21')
            ->where('calendarItem.endDate', '2026-10-23')
            ->where('calendarItem.startTime', null)
            ->where('calendarItem.endTime', null)
            // Made timed again, it starts from its sub-category's default.
            ->where('calendarItem.timeType', 'block'));
});

test('the edit form is filled in with a calendar item that ends on a later day', function () {
    $user = User::factory()->create();
    $calendarItem = CalendarItem::factory()->for($user)->between('2026-10-21 22:00', '2026-10-22 02:00')->create();

    $this->actingAs($user)
        ->get(route('calendar-items.edit', $calendarItem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('calendarItem.date', '2026-10-21')
            ->where('calendarItem.endDate', '2026-10-22')
            ->where('calendarItem.startTime', '22:00')
            ->where('calendarItem.endTime', '02:00'));
});

test('cancelling returns to the calendar view the edit form was opened from, on the calendar item\'s day', function (?string $view, string $backUrl) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('calendar-items.edit', [dentist($user), 'view' => $view]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('view', $view ?? 'list')
            ->where('backUrl', url($backUrl)));
})->with([
    'no view' => [null, '/calendar-items'],
    'list' => ['list', '/calendar-items'],
    'day' => ['day', '/calendar-items/day/2026-10-21'],
    'week' => ['week', '/calendar-items/week/2026-10-21'],
    'month' => ['month', '/calendar-items/month/2026-10-21'],
]);

test('the edit form cannot be opened with a calendar view that does not exist', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('calendar-items.edit', [dentist($user), 'view' => 'year']))
        ->assertSessionHasErrors('view');
});

test('a user can change every detail of their calendar item', function () {
    $user = User::factory()->create();
    $subCategory = SubCategory::factory()->for(Category::factory()->for($user))->create(['default_time_type' => TimeType::Exact]);
    [$prePaid, $nonCancellable, $important] = $user->flags()->orderBy('id')->get()->all();
    $calendarItem = dentist($user);
    $calendarItem->flags()->attach($prePaid);

    $this->actingAs($user)
        ->put(route('calendar-items.update', $calendarItem), [
            ...UPDATED_CALENDAR_ITEM,
            'sub_category_id' => $subCategory->id,
            'flag_ids' => [$important->id, $nonCancellable->id],
            'location_name' => 'Smile Dental Practice',
            'location_address' => '12 High Street',
            'location_latitude' => '55.95',
            'location_longitude' => '-3.18',
        ])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Calendar item updated.'])
        ->assertRedirect(route('calendar-items.index'));

    $calendarItem->refresh();

    expect($calendarItem)
        ->title->toBe('Dentist check-up')
        ->starts_at->toDateTimeString()->toBe('2026-10-22 14:00:00')
        ->ends_at->toDateTimeString()->toBe('2026-10-22 15:15:00')
        ->all_day->toBeFalse()
        ->sub_category_id->toBe($subCategory->id)
        ->time_type->toBe(TimeType::Window)
        ->time_type_overridden->toBeTrue()
        ->firmness->toBe(Firmness::Tentative)
        ->location_name->toBe('Smile Dental Practice')
        ->location_address->toBe('12 High Street')
        ->location_latitude->toBe(55.95)
        ->location_longitude->toBe(-3.18)
        ->user_id->toBe($user->id)
        ->and($calendarItem->flags->pluck('id')->all())->toBe([$nonCancellable->id, $important->id])
        ->and(CalendarItem::count())->toBe(1);
});

test('a calendar item\'s category, flags and location can all be taken off', function () {
    $user = User::factory()->create();
    $subCategory = SubCategory::factory()->for(Category::factory()->for($user))->create();
    $calendarItem = CalendarItem::factory()->for($user)->for($subCategory)->withLocation()->between('2026-10-21 09:00', '2026-10-21 10:30')->create();
    $calendarItem->flags()->attach($user->flags()->pluck('id'));

    $this->actingAs($user)
        ->put(route('calendar-items.update', $calendarItem), [...UPDATED_CALENDAR_ITEM, 'sub_category_id' => '', 'location_name' => ''])
        ->assertSessionHasNoErrors();

    $calendarItem->refresh();

    expect($calendarItem->sub_category_id)->toBeNull()
        ->and($calendarItem->time_type_overridden)->toBeFalse()
        ->and($calendarItem->location_name)->toBeNull()
        ->and($calendarItem->location_latitude)->toBeNull()
        ->and($calendarItem->flags)->toBeEmpty();
});

test('a timed calendar item can be made all day across several days, and back', function () {
    $user = User::factory()->create();
    $calendarItem = dentist($user);

    $this->actingAs($user)
        ->put(route('calendar-items.update', $calendarItem), [...UPDATED_CALENDAR_ITEM, 'all_day' => '1', 'end_date' => '2026-10-24'])
        ->assertSessionHasNoErrors();

    expect($calendarItem->refresh())
        ->all_day->toBeTrue()
        ->starts_at->toDateTimeString()->toBe('2026-10-22 00:00:00')
        ->ends_at->toDateTimeString()->toBe('2026-10-25 00:00:00');

    $this->actingAs($user)
        ->put(route('calendar-items.update', $calendarItem), [...UPDATED_CALENDAR_ITEM, 'all_day' => '0'])
        ->assertSessionHasNoErrors();

    expect($calendarItem->refresh())
        ->all_day->toBeFalse()
        ->starts_at->toDateTimeString()->toBe('2026-10-22 14:00:00')
        ->ends_at->toDateTimeString()->toBe('2026-10-22 15:15:00');
});

test('saving returns to the calendar view it was edited from, on the day the calendar item now starts', function (string $view, string $date, string $redirect) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('calendar-items.update', dentist($user)), [...UPDATED_CALENDAR_ITEM, 'date' => $date, 'view' => $view])
        ->assertSessionHasNoErrors()
        ->assertRedirect(url($redirect));
})->with([
    'list, from today on' => ['list', '2026-10-22', '/calendar-items'],
    'list, moved before today' => ['list', '2026-10-01', '/calendar-items?from=2026-10-01'],
    'day' => ['day', '2026-11-03', '/calendar-items/day/2026-11-03'],
    'week' => ['week', '2026-11-03', '/calendar-items/week/2026-11-03'],
    'month' => ['month', '2026-11-03', '/calendar-items/month/2026-11-03'],
]);

test('a calendar view that does not exist is not returned to and nothing is changed', function () {
    $user = User::factory()->create();
    $calendarItem = dentist($user);

    $this->actingAs($user)
        ->put(route('calendar-items.update', $calendarItem), [...UPDATED_CALENDAR_ITEM, 'view' => 'https://example.com'])
        ->assertSessionHasErrors('view');

    expect($calendarItem->fresh()->title)->toBe('Dentist');
});

test('the same checks apply as when creating a calendar item and nothing is changed', function (array $data, array $errors) {
    $user = User::factory()->create();
    $calendarItem = dentist($user);
    $before = $calendarItem->fresh()->toArray();

    $this->actingAs($user)
        ->put(route('calendar-items.update', $calendarItem), [...UPDATED_CALENDAR_ITEM, ...$data])
        ->assertSessionHasErrors($errors);

    expect($calendarItem->fresh()->toArray())->toBe($before);
})->with([
    'no title' => [['title' => ''], ['title' => 'Please enter a title.']],
    'end before start' => [['end_time' => '13:00'], ['end_time' => 'The end must be after the start.']],
    'end date before start date' => [['end_date' => '2026-10-21'], ['end_date' => 'The end must be after the start.']],
    'no time type' => [['time_type' => ''], ['time_type' => 'Please choose a time type.']],
    'no firmness' => [['firmness' => ''], ['firmness' => 'Please choose how firm it is.']],
    'a sub-category that is not a number' => [['sub_category_id' => 'other'], ['sub_category_id' => 'Please choose a category again.']],
]);

test('a calendar item cannot be given another user\'s sub-category or flag', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $calendarItem = dentist($user);

    $this->actingAs($user)
        ->put(route('calendar-items.update', $calendarItem), [
            ...UPDATED_CALENDAR_ITEM,
            'sub_category_id' => SubCategory::factory()->for(Category::factory()->for($other))->create()->id,
            'flag_ids' => [$other->flags()->value('id')],
        ])
        ->assertSessionHasErrors(['sub_category_id', 'flag_ids.0']);

    expect($calendarItem->fresh()->title)->toBe('Dentist');
});

test('a user gets a not found page for another user\'s calendar item and nothing is saved', function () {
    $calendarItem = dentist(User::factory()->create());
    $intruder = User::factory()->create();

    $this->actingAs($intruder)
        ->get(route('calendar-items.edit', $calendarItem))
        ->assertNotFound();

    $this->actingAs($intruder)
        ->put(route('calendar-items.update', $calendarItem), UPDATED_CALENDAR_ITEM)
        ->assertNotFound();

    expect($calendarItem->fresh()->title)->toBe('Dentist');
});

test('a user gets a not found page for a calendar item that does not exist', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('calendar-items.edit', 999))
        ->assertNotFound();
});

test('editing a calendar item is logged with only what changed', function () {
    $user = User::factory()->create();
    $calendarItem = dentist($user);

    $this->actingAs($user)
        ->put(route('calendar-items.update', $calendarItem), [
            'title' => 'Dentist check-up',
            'date' => '2026-10-21',
            'start_time' => '09:00',
            'end_time' => '10:30',
            'time_type' => 'exact',
            'firmness' => 'confirmed',
        ])
        ->assertSessionHasNoErrors();

    $activity = Activity::query()->whereMorphedTo('subject', $calendarItem)->latest('id')->firstOrFail();

    expect($activity->event)->toBe('updated')
        ->and(array_keys($activity->attribute_changes['attributes']))->toBe(['title']);
});
