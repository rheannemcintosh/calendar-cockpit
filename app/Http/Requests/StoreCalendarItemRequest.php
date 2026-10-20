<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
     * Combine the date and times into the start and end the calendar item is stored with.
     *
     * @return array{title: string, starts_at: CarbonImmutable, ends_at: CarbonImmutable}
     */
    public function calendarItemAttributes(): array
    {
        $date = $this->string('date');

        return [
            'title' => $this->string('title')->toString(),
            'starts_at' => CarbonImmutable::parse("{$date} {$this->string('start_time')}"),
            'ends_at' => CarbonImmutable::parse("{$date} {$this->string('end_time')}"),
        ];
    }
}
