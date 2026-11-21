<?php

namespace App\Policies;

use App\Models\CalendarItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CalendarItemPolicy
{
    /**
     * Only the user who owns a calendar item can change it. Anyone else gets
     * a not found page, so they cannot tell it exists.
     */
    public function update(User $user, CalendarItem $calendarItem): Response
    {
        return $calendarItem->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
