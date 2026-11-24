<?php

use App\Enums\Firmness;
use App\Enums\TimeType;
use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

// Created at 09:05 UTC on Saturday 21 November 2026, which is 09:05 in London
// and 22:05 in Auckland.
beforeEach(function () {
    $this->travelTo('2026-11-21 09:05:00');
    $this->user = User::factory()->create(['timezone' => 'Europe/London']);
    $this->actingAs($this->user);
});

/**
 * A calendar item from 10:00 to 11:00 on Monday 23 November 2026, made now, for the user.
 *
 * @param  array<string, mixed>  $attributes
 */
function historyItem(User $user, array $attributes = []): CalendarItem
{
    return CalendarItem::factory()->for($user)->between('2026-11-23 10:00', '2026-11-23 11:00')->create(['title' => 'Dentist', ...$attributes]);
}

test('guests are sent to the login page from a calendar item\'s history', function () {
    $calendarItem = historyItem($this->user);

    auth()->logout();

    $this->get(route('calendar-items.history', $calendarItem))->assertRedirect(route('login'));
});

test('a calendar item belonging to another user has no history to see', function () {
    $calendarItem = historyItem(User::factory()->create());

    $this->get(route('calendar-items.history', $calendarItem))->assertNotFound();
});

test('a calendar item that has not been changed has a single entry saying it was created', function () {
    $calendarItem = historyItem($this->user);

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('calendar-items/History')
            ->where('calendarItem', ['id' => $calendarItem->id, 'title' => 'Dentist'])
            ->has('entries', 1)
            ->where('entries.0.event', 'created')
            ->where('entries.0.at', '21 Nov 2026, 09:05')
            ->where('entries.0.changes', []));
});

test('each change is shown newest first, with the fields that changed and their old and new values', function () {
    $calendarItem = historyItem($this->user);

    $this->travelTo('2026-11-21 10:30:00');
    $calendarItem->update(['title' => 'Dentist check-up']);

    $this->travelTo('2026-11-21 14:05:00');
    $calendarItem->update([
        'starts_at' => '2026-11-24 14:00',
        'ends_at' => '2026-11-24 15:15',
        'time_type' => TimeType::Window,
        'time_type_overridden' => true,
        'firmness' => Firmness::Tentative,
    ]);

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 3)
            ->where('entries.0.event', 'updated')
            ->where('entries.0.at', '21 Nov 2026, 14:05')
            ->where('entries.0.changes', [
                ['field' => 'starts_at', 'label' => 'Starts', 'old' => '23 Nov 2026, 10:00', 'new' => '24 Nov 2026, 14:00'],
                ['field' => 'ends_at', 'label' => 'Ends', 'old' => '23 Nov 2026, 11:00', 'new' => '24 Nov 2026, 15:15'],
                ['field' => 'time_type', 'label' => 'Time type', 'old' => 'Exact', 'new' => 'Between'],
                ['field' => 'time_type_overridden', 'label' => 'Time type chosen by you', 'old' => 'No', 'new' => 'Yes'],
                ['field' => 'firmness', 'label' => 'Firmness', 'old' => 'Confirmed', 'new' => 'Tentative'],
            ])
            ->where('entries.1.at', '21 Nov 2026, 10:30')
            ->where('entries.1.changes', [
                ['field' => 'title', 'label' => 'Title', 'old' => 'Dentist', 'new' => 'Dentist check-up'],
            ])
            ->where('entries.2.event', 'created'));
});

test('a changed sub-category shows its name, even once it has been deleted', function () {
    $category = Category::factory()->for($this->user)->create();
    $dentist = SubCategory::factory()->for($category)->create(['name' => 'Dentist']);
    $optician = SubCategory::factory()->for($category)->create(['name' => 'Optician']);
    $calendarItem = historyItem($this->user, ['sub_category_id' => $dentist->id]);

    $calendarItem->update(['sub_category_id' => $optician->id]);
    $dentist->delete();
    $calendarItem->update(['sub_category_id' => null]);

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.changes', [
                ['field' => 'sub_category_id', 'label' => 'Sub-category', 'old' => 'Optician', 'new' => null],
            ])
            ->where('entries.1.changes', [
                ['field' => 'sub_category_id', 'label' => 'Sub-category', 'old' => 'Dentist', 'new' => 'Optician'],
            ]));
});

test('an all-day calendar item\'s dates show without times, ending on its last day', function () {
    $calendarItem = historyItem($this->user);

    $calendarItem->update([
        'all_day' => true,
        'starts_at' => '2026-11-23 00:00',
        'ends_at' => '2026-11-25 00:00',
    ]);

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.changes', [
                ['field' => 'starts_at', 'label' => 'Starts', 'old' => '23 Nov 2026, 10:00', 'new' => '23 Nov 2026'],
                ['field' => 'ends_at', 'label' => 'Ends', 'old' => '23 Nov 2026, 11:00', 'new' => '24 Nov 2026'],
                ['field' => 'all_day', 'label' => 'All day', 'old' => 'No', 'new' => 'Yes'],
            ]));
});

test('changed time before and after show in hours and minutes, or none', function () {
    $calendarItem = historyItem($this->user);

    $calendarItem->update(['minutes_before' => 30, 'minutes_after' => 150]);
    $calendarItem->update(['minutes_before' => 60, 'minutes_after' => 0]);

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.changes', [
                ['field' => 'minutes_before', 'label' => 'Time before', 'old' => '30 min', 'new' => '1 hr'],
                ['field' => 'minutes_after', 'label' => 'Time after', 'old' => '2 hr 30 min', 'new' => 'None'],
            ])
            ->where('entries.1.changes', [
                ['field' => 'minutes_before', 'label' => 'Time before', 'old' => 'None', 'new' => '30 min'],
                ['field' => 'minutes_after', 'label' => 'Time after', 'old' => 'None', 'new' => '2 hr 30 min'],
            ]));
});

test('making a calendar item all day shows on its history that its time before and after were removed', function () {
    $calendarItem = historyItem($this->user, ['minutes_before' => 45, 'minutes_after' => 15]);

    $this->put(route('calendar-items.update', $calendarItem), [
        'title' => 'Dentist',
        'all_day' => '1',
        'date' => '2026-11-23',
        'end_date' => '2026-11-23',
        'time_type' => 'exact',
        'firmness' => 'confirmed',
    ])->assertSessionHasNoErrors();

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.changes', fn ($changes) => collect($changes)->whereIn('field', ['minutes_before', 'minutes_after'])->values()->all() === [
                ['field' => 'minutes_before', 'label' => 'Time before', 'old' => '45 min', 'new' => 'None'],
                ['field' => 'minutes_after', 'label' => 'Time after', 'old' => '15 min', 'new' => 'None'],
            ]));
});

test('a changed location shows as one field with its name and address', function () {
    $calendarItem = historyItem($this->user);

    $calendarItem->update([
        'location_name' => 'Smile Dental Practice',
        'location_address' => '12 High Street',
        'location_latitude' => 55.95,
        'location_longitude' => -3.18,
    ]);

    $calendarItem->update([
        'location_name' => 'Bright Smiles',
        'location_address' => null,
        'location_latitude' => 55.96,
        'location_longitude' => -3.19,
    ]);

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.changes', [
                ['field' => 'location', 'label' => 'Location', 'old' => 'Smile Dental Practice, 12 High Street', 'new' => 'Bright Smiles'],
            ])
            ->where('entries.1.changes', [
                ['field' => 'location', 'label' => 'Location', 'old' => null, 'new' => 'Smile Dental Practice, 12 High Street'],
            ]));
});

test('when a change was made is shown in the user\'s timezone', function () {
    $this->user->update(['timezone' => 'Pacific/Auckland']);
    $calendarItem = historyItem($this->user);

    $this->travelTo('2026-11-22 08:00:00');
    $calendarItem->update(['title' => 'Dentist check-up']);

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.at', '22 Nov 2026, 21:00')
            ->where('entries.1.at', '21 Nov 2026, 22:05'));
});

test('a calendar item made before changes were logged still shows when it was created', function () {
    $calendarItem = historyItem($this->user);
    Activity::query()->whereMorphedTo('subject', $calendarItem)->delete();

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 1)
            ->where('entries.0.event', 'created')
            ->where('entries.0.at', '21 Nov 2026, 09:05'));
});

test('a calendar item\'s history goes back to the calendar view it was opened from', function () {
    $calendarItem = historyItem($this->user);

    $this->get(route('calendar-items.history', [$calendarItem, 'view' => 'week']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('view', 'week')
            ->where('backUrl', route('calendar-items.week', '2026-11-23')));
});

/**
 * A calendar item in the user's Dentist sub-category, in their Health category,
 * both made before the calendar item.
 *
 * @return array{0: Category, 1: SubCategory, 2: CalendarItem}
 */
function categorisedHistoryItem(User $user): array
{
    $category = Category::factory()->for($user)->create(['name' => 'Health', 'colour' => '#16A34A']);
    $subCategory = SubCategory::factory()->for($category)->create(['emoji' => '🦷', 'name' => 'Dentist']);

    return [$category, $subCategory, historyItem($user, ['sub_category_id' => $subCategory->id])];
}

test('renaming a calendar item\'s sub-category shows on its history as coming from the sub-category', function () {
    [, $subCategory, $calendarItem] = categorisedHistoryItem($this->user);

    $this->travelTo('2026-11-21 11:00:00');
    $subCategory->update(['name' => 'Dental']);

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 2)
            ->where('entries.0.event', 'updated')
            ->where('entries.0.at', '21 Nov 2026, 11:00')
            ->where('entries.0.source', ['type' => 'sub_category', 'action' => 'updated', 'name' => 'Dental'])
            ->where('entries.0.changes', [
                ['field' => 'name', 'label' => 'Name', 'old' => 'Dentist', 'new' => 'Dental'],
            ])
            ->where('entries.1.event', 'created')
            ->where('entries.1.source', null));
});

test('changing how a category or sub-category looks, or a sub-category\'s default time type, leaves its calendar items\' history as it was', function () {
    [$category, $subCategory, $calendarItem] = categorisedHistoryItem($this->user);

    $subCategory->update(['emoji' => '😁']);
    $subCategory->update(['default_time_type' => TimeType::Window]);
    $category->update(['colour' => '#2563EB']);

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page->has('entries', 1));
});

test('renaming a calendar item\'s category shows on its history as coming from the category', function () {
    [$category, , $calendarItem] = categorisedHistoryItem($this->user);

    $this->travelTo('2026-11-21 11:00:00');
    $category->update(['name' => 'Wellbeing', 'colour' => '#2563EB']);

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 2)
            ->where('entries.0.at', '21 Nov 2026, 11:00')
            ->where('entries.0.source', ['type' => 'category', 'action' => 'updated', 'name' => 'Wellbeing'])
            ->where('entries.0.changes', [
                ['field' => 'name', 'label' => 'Name', 'old' => 'Health', 'new' => 'Wellbeing'],
            ]));
});

test('moving a calendar item\'s sub-category to another category shows on its history, and so do changes to the new category', function () {
    [$health, $subCategory, $calendarItem] = categorisedHistoryItem($this->user);
    $appointments = Category::factory()->for($this->user)->create(['name' => 'Appointments']);

    // The app has no way to move a sub-category yet, but it is logged when one moves.
    $subCategory->forceFill(['category_id' => $appointments->id])->save();
    $health->update(['name' => 'Wellbeing']);
    $appointments->update(['name' => 'Bookings']);

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 3)
            ->where('entries.0.source', ['type' => 'category', 'action' => 'updated', 'name' => 'Bookings'])
            ->where('entries.0.changes', [
                ['field' => 'name', 'label' => 'Name', 'old' => 'Appointments', 'new' => 'Bookings'],
            ])
            ->where('entries.1.source', ['type' => 'sub_category', 'action' => 'updated', 'name' => 'Dentist'])
            ->where('entries.1.changes', [
                ['field' => 'category_id', 'label' => 'Category', 'old' => 'Wellbeing', 'new' => 'Bookings'],
            ])
            ->where('entries.2.event', 'created'));
});

test('deleting a calendar item\'s sub-category shows on its history that it became uncategorised because of it', function () {
    [, $subCategory, $calendarItem] = categorisedHistoryItem($this->user);

    $this->travelTo('2026-11-21 11:00:00');
    $this->delete(route('sub-categories.destroy', $subCategory))->assertRedirect();

    expect($calendarItem->fresh()->sub_category_id)->toBeNull();

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 2)
            ->where('entries.0.at', '21 Nov 2026, 11:00')
            ->where('entries.0.source', ['type' => 'sub_category', 'action' => 'deleted', 'name' => 'Dentist'])
            ->where('entries.0.changes', [
                ['field' => 'sub_category_id', 'label' => 'Sub-category', 'old' => 'Dentist', 'new' => null],
            ]));
});

test('deleting a calendar item\'s category shows on its history that it became uncategorised because of it', function () {
    [$category, , $calendarItem] = categorisedHistoryItem($this->user);
    $other = historyItem($this->user, ['sub_category_id' => SubCategory::factory()->for($category)->create(['name' => 'Optician'])->id]);

    $this->delete(route('categories.destroy', $category))->assertRedirect();

    expect($calendarItem->fresh()->sub_category_id)->toBeNull()
        ->and($other->fresh()->sub_category_id)->toBeNull();

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 2)
            ->where('entries.0.source', ['type' => 'category', 'action' => 'deleted', 'name' => 'Health'])
            ->where('entries.0.changes', [
                ['field' => 'sub_category_id', 'label' => 'Sub-category', 'old' => 'Dentist', 'new' => null],
            ]));

    $this->get(route('calendar-items.history', $other))
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.0.changes.0.old', 'Optician'));

    expect(Activity::query()->whereMorphedTo('subject', $calendarItem)->latest('id')->first()->causer_id)->toEqual($this->user->id);
});

test('changes made to a category or sub-category before or after a calendar item was in it don\'t show on its history', function () {
    [$category, $subCategory] = categorisedHistoryItem($this->user);
    $calendarItem = historyItem($this->user);

    $subCategory->update(['name' => 'Dental']);
    $category->update(['name' => 'Wellbeing']);

    $calendarItem->update(['sub_category_id' => $subCategory->id]);
    $category->update(['name' => 'Self-care']);

    $calendarItem->update(['sub_category_id' => null]);
    $subCategory->update(['name' => 'Teeth']);
    $category->update(['name' => 'Health']);

    $this->get(route('calendar-items.history', $calendarItem))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries', 4)
            ->where('entries.0.source', null)
            ->where('entries.1.source', ['type' => 'category', 'action' => 'updated', 'name' => 'Self-care'])
            ->where('entries.1.changes', [
                ['field' => 'name', 'label' => 'Name', 'old' => 'Wellbeing', 'new' => 'Self-care'],
            ])
            ->where('entries.2.source', null)
            ->where('entries.3.event', 'created'));
});
