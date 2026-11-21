<?php

namespace App\Http\Requests;

use App\Enums\Firmness;
use App\Enums\TimeType;
use App\Models\SubCategory;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalendarItemRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],
            // The end date is the start date unless a later one is chosen.
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date'],
            'all_day' => ['nullable', 'boolean'],
            // An all-day calendar item has dates but no times or time type.
            // An end time earlier than the start time is fine on a later end date.
            'start_time' => [Rule::excludeIf($this->allDay()), 'required', 'date_format:H:i,H:i:s'],
            'end_time' => [Rule::excludeIf($this->allDay()), 'required', 'date_format:H:i,H:i:s', Rule::when($this->endsOnStartDate(), 'after:start_time')],
            'sub_category_id' => [
                'bail',
                'nullable',
                'integer',
                Rule::exists('sub_categories', 'id')->using(
                    fn (Builder $query) => $query->whereIn('category_id', $this->user()->categories()->select('id')),
                ),
            ],
            'time_type' => [Rule::excludeIf($this->allDay()), 'required', Rule::enum(TimeType::class)],
            'firmness' => ['required', Rule::enum(Firmness::class)],
            // Any number of the user's own flags, including none.
            'flag_ids' => ['nullable', 'array'],
            'flag_ids.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('flags', 'id')->where('user_id', $this->user()->id),
            ],
            // A location is optional. When one is chosen it comes from a
            // location search, so it always has a name and a point on the map.
            // Without a name, the rest of the location is ignored.
            'location_name' => ['nullable', 'string', 'max:255'],
            'location_address' => ['exclude_without:location_name', 'nullable', 'string', 'max:255'],
            'location_latitude' => ['exclude_without:location_name', 'required', 'numeric', 'between:-90,90'],
            'location_longitude' => ['exclude_without:location_name', 'required', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => __('Please enter a title.'),
            'date.required' => __('Please choose a date.'),
            'end_date.date_format' => __('Please choose an end date again.'),
            'end_date.after_or_equal' => __('The end must be after the start.'),
            'all_day.boolean' => __('Please choose whether it is all day again.'),
            'start_time.required' => __('Please choose a start time.'),
            'end_time.required' => __('Please choose an end time.'),
            'end_time.after' => __('The end must be after the start.'),
            'sub_category_id.integer' => __('Please choose a category again.'),
            'sub_category_id.exists' => __('Please choose a category again.'),
            'time_type.required' => __('Please choose a time type.'),
            'time_type.enum' => __('Please choose a time type again.'),
            'firmness.required' => __('Please choose how firm it is.'),
            'firmness.enum' => __('Please choose how firm it is again.'),
            'flag_ids.*' => __('Please choose the flags again.'),
            'location_name.*' => __('Please choose the location again.'),
            'location_address.*' => __('Please choose the location again.'),
            'location_latitude.*' => __('Please choose the location again.'),
            'location_longitude.*' => __('Please choose the location again.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'start_time' => __('start time'),
            'end_time' => __('end time'),
            'end_date' => __('end date'),
        ];
    }

    /**
     * Combine the date and times into the start and end the calendar item is
     * stored with. Only the sub-category is stored, since it sets the category.
     * The time type is overridden when it differs from the sub-category's
     * default, which is worked out here rather than trusted from the form.
     * A timed calendar item ends at the end time on the end date. An all-day
     * calendar item runs from the start date's midnight to the midnight after
     * the end date, covering both, and keeps the default time type, which it doesn't show, never overridden.
     * Every part of the location is null when no location is chosen.
     *
     * @return array{title: string, starts_at: CarbonImmutable, ends_at: CarbonImmutable, all_day: bool, sub_category_id: int|null, time_type: TimeType, time_type_overridden: bool, firmness: Firmness, location_name: string|null, location_address: string|null, location_latitude: float|null, location_longitude: float|null}
     */
    public function calendarItemAttributes(): array
    {
        $date = $this->string('date');
        $allDay = $this->allDay();
        $subCategory = $this->filled('sub_category_id') ? SubCategory::findOrFail($this->integer('sub_category_id')) : null;
        $timeType = $allDay ? TimeType::Exact : ($this->enum('time_type', TimeType::class) ?? TimeType::Exact);
        $hasLocation = $this->filled('location_name');
        $day = CarbonImmutable::createFromFormat('!Y-m-d', $date->toString());
        $endDate = $this->filled('end_date') ? $this->string('end_date') : $date;
        $endDay = CarbonImmutable::createFromFormat('!Y-m-d', $endDate->toString());

        return [
            'title' => $this->string('title')->toString(),
            'starts_at' => $allDay ? $day : CarbonImmutable::parse("{$date} {$this->string('start_time')}"),
            'ends_at' => $allDay ? $endDay->addDay() : CarbonImmutable::parse("{$endDate} {$this->string('end_time')}"),
            'all_day' => $allDay,
            'sub_category_id' => $subCategory?->id,
            'time_type' => $timeType,
            'time_type_overridden' => ! $allDay && $subCategory !== null && $subCategory->default_time_type !== $timeType,
            'firmness' => $this->enum('firmness', Firmness::class) ?? Firmness::Confirmed,
            'location_name' => $hasLocation ? $this->string('location_name')->toString() : null,
            'location_address' => $hasLocation && $this->filled('location_address') ? $this->string('location_address')->toString() : null,
            'location_latitude' => $hasLocation ? $this->float('location_latitude') : null,
            'location_longitude' => $hasLocation ? $this->float('location_longitude') : null,
        ];
    }

    /**
     * Whether the calendar item ends on the day it starts, so its end time
     * must be after its start time.
     */
    public function endsOnStartDate(): bool
    {
        return ! $this->filled('end_date') || $this->input('end_date') === $this->input('date');
    }

    /**
     * Whether the calendar item is all day, which it isn't unless asked for.
     */
    public function allDay(): bool
    {
        return $this->boolean('all_day');
    }

    /**
     * The ids of the flags chosen for the calendar item, which may be none.
     *
     * @return list<int>
     */
    public function flagIds(): array
    {
        return array_values(array_map('intval', $this->array('flag_ids')));
    }
}
