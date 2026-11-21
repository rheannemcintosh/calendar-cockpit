<?php

use App\Enums\TimeType;
use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

/**
 * The newest activity entry for the given model.
 */
function latestActivityFor(Model $model): Activity
{
    return Activity::query()
        ->whereMorphedTo('subject', $model)
        ->latest('id')
        ->firstOrFail();
}

dataset('logged models', [
    'calendar item' => [
        'calendar_item',
        fn () => CalendarItem::factory()->for(test()->user)->create(['title' => 'Dentist']),
        ['title' => 'Dentist check-up'],
    ],
    'category' => [
        'category',
        fn () => Category::factory()->for(test()->user)->create(['name' => 'Health']),
        ['name' => 'Wellbeing'],
    ],
    'sub-category' => [
        'sub_category',
        fn () => SubCategory::factory()->for(Category::factory()->for(test()->user))->create(['name' => 'Dentist']),
        ['name' => 'Orthodontist'],
    ],
]);

test('creating a model logs a created entry linked to it and the user', function (string $logName, Closure $make) {
    $model = $make();

    $activity = latestActivityFor($model);

    expect($activity->log_name)->toBe($logName)
        ->and($activity->event)->toBe('created')
        ->and($activity->subject->is($model))->toBeTrue()
        ->and($activity->causer->is($this->user))->toBeTrue()
        ->and($activity->attribute_changes['attributes'])->not->toBeEmpty()
        ->and($activity->attribute_changes['attributes'])->not->toHaveKeys(['created_at', 'updated_at']);
})->with('logged models');

test('updating a model logs only the fields that changed', function (string $logName, Closure $make, array $changes) {
    $model = $make();
    $field = array_key_first($changes);
    $old = $model->{$field};

    $model->update($changes);

    $activity = latestActivityFor($model);

    expect($activity->log_name)->toBe($logName)
        ->and($activity->event)->toBe('updated')
        ->and($activity->causer->is($this->user))->toBeTrue()
        ->and($activity->attribute_changes['attributes'])->toBe($changes)
        ->and($activity->attribute_changes['old'])->toBe([$field => $old]);
})->with('logged models');

test('an update that changes nothing logs no entry', function (string $logName, Closure $make, array $changes) {
    $model = $make();
    $field = array_key_first($changes);

    $model->update([$field => $model->{$field}]);
    $model->touch();

    expect(Activity::query()->whereMorphedTo('subject', $model)->pluck('event')->all())->toBe(['created']);
})->with('logged models');

test('deleting a model logs a deleted entry with its old values', function (string $logName, Closure $make, array $changes) {
    $model = $make();
    $field = array_key_first($changes);
    $old = $model->{$field};

    $model->delete();

    $activity = latestActivityFor($model);

    expect($activity->log_name)->toBe($logName)
        ->and($activity->event)->toBe('deleted')
        ->and((int) $activity->subject_id)->toBe($model->id)
        ->and($activity->causer->is($this->user))->toBeTrue()
        ->and($activity->attribute_changes['old'][$field])->toBe($old)
        ->and($activity->attribute_changes->has('attributes'))->toBeFalse();
})->with('logged models');

test('enum fields are logged as their stored values', function () {
    $item = CalendarItem::factory()->for($this->user)->create();

    $item->update(['time_type' => TimeType::Window]);

    expect(latestActivityFor($item)->attribute_changes->all())->toBe([
        'attributes' => ['time_type' => TimeType::Window->value],
        'old' => ['time_type' => TimeType::Exact->value],
    ]);
});

test('entries can be found by a changed value', function () {
    $category = Category::factory()->for($this->user)->create();
    [$dentist, $optician] = SubCategory::factory()->for($category)->count(2)->create();
    $moved = CalendarItem::factory()->for($this->user)->create(['sub_category_id' => $dentist->id]);
    $renamed = CalendarItem::factory()->for($this->user)->create(['sub_category_id' => $dentist->id]);

    $moved->update(['sub_category_id' => $optician->id]);
    $renamed->update(['title' => 'Something else']);

    $entries = Activity::query()
        ->inLog('calendar_item')
        ->where('event', 'updated')
        ->where('attribute_changes->attributes->sub_category_id', $optician->id)
        ->get();

    expect($entries)->toHaveCount(1)
        ->and($entries->first()->subject->is($moved))->toBeTrue()
        ->and($entries->first()->attribute_changes['old']['sub_category_id'])->toBe($dentist->id);
});
