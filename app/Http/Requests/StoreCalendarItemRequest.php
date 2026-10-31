<?php

namespace App\Http\Requests;

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
            'start_time' => ['required', 'date_format:H:i,H:i:s'],
            'end_time' => ['required', 'date_format:H:i,H:i:s', 'after:start_time'],
            'sub_category_id' => [
                'bail',
                'nullable',
                'integer',
                Rule::exists('sub_categories', 'id')->using(
                    fn (Builder $query) => $query->whereIn('category_id', $this->user()->categories()->select('id')),
                ),
            ],
            'time_type' => ['required', Rule::enum(TimeType::class)],
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
            'start_time.required' => __('Please choose a start time.'),
            'end_time.required' => __('Please choose an end time.'),
            'end_time.after' => __('The end time must be after the start time.'),
            'sub_category_id.integer' => __('Please choose a category again.'),
            'sub_category_id.exists' => __('Please choose a category again.'),
            'time_type.required' => __('Please choose a time type.'),
            'time_type.enum' => __('Please choose a time type again.'),
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
        ];
    }

    /**
     * Combine the date and times into the start and end the calendar item is
     * stored with. Only the sub-category is stored, since it sets the category.
     * The time type is overridden when it differs from the sub-category's
     * default, which is worked out here rather than trusted from the form.
     *
     * @return array{title: string, starts_at: CarbonImmutable, ends_at: CarbonImmutable, sub_category_id: int|null, time_type: TimeType, time_type_overridden: bool}
     */
    public function calendarItemAttributes(): array
    {
        $date = $this->string('date');
        $subCategory = $this->filled('sub_category_id') ? SubCategory::findOrFail($this->integer('sub_category_id')) : null;
        $timeType = $this->enum('time_type', TimeType::class) ?? TimeType::Exact;

        return [
            'title' => $this->string('title')->toString(),
            'starts_at' => CarbonImmutable::parse("{$date} {$this->string('start_time')}"),
            'ends_at' => CarbonImmutable::parse("{$date} {$this->string('end_time')}"),
            'sub_category_id' => $subCategory?->id,
            'time_type' => $timeType,
            'time_type_overridden' => $subCategory !== null && $subCategory->default_time_type !== $timeType,
        ];
    }
}
