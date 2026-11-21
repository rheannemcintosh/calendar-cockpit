<?php

namespace App\Http\Requests;

use App\Enums\CalendarView;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The same checks as creating a calendar item, plus the calendar view the
 * edit page was opened from, which is returned to once it is saved.
 */
class UpdateCalendarItemRequest extends StoreCalendarItemRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'view' => ['nullable', Rule::enum(CalendarView::class)],
        ];
    }

    /**
     * The calendar view to return to, the list unless another is given.
     */
    public function view(): CalendarView
    {
        return $this->enum('view', CalendarView::class) ?? CalendarView::List;
    }
}
