<?php

use App\Enums\TimeType;

/*
|--------------------------------------------------------------------------
| Starter Categories
|--------------------------------------------------------------------------
|
| The categories and sub-categories a user can start from, as set out in
| product/starter-categories.md. They are only created for a user who asks
| for them, by App\Actions\CreateStarterCategories, never by a seeder or a
| migration. Change the product document and this file together.
|
*/

return [

    [
        'name' => 'Work',
        'colour' => '#3B82F6',
        'sub_categories' => [
            ['emoji' => '🏢', 'name' => 'In Office', 'default_time_type' => TimeType::Block->value],
            ['emoji' => '🏠', 'name' => 'Working from Home', 'default_time_type' => TimeType::Block->value],
            ['emoji' => '🎯', 'name' => 'Focus Time', 'default_time_type' => TimeType::Block->value],
            ['emoji' => '🌴', 'name' => 'Annual Leave', 'default_time_type' => TimeType::Block->value],
            ['emoji' => '📌', 'name' => 'Work Milestones', 'default_time_type' => TimeType::Block->value],
        ],
    ],

    [
        'name' => 'Getting Around',
        'colour' => '#6B7280',
        'sub_categories' => [
            ['emoji' => '🚶', 'name' => 'Walk', 'default_time_type' => TimeType::Approximate->value],
            ['emoji' => '🚌', 'name' => 'Bus', 'default_time_type' => TimeType::Approximate->value],
            ['emoji' => '🚕', 'name' => 'Taxi', 'default_time_type' => TimeType::Approximate->value],
            ['emoji' => '🚇', 'name' => 'Tube', 'default_time_type' => TimeType::Approximate->value],
            ['emoji' => '🚆', 'name' => 'Train', 'default_time_type' => TimeType::Exact->value],
        ],
    ],

    [
        'name' => 'Travel',
        'colour' => '#06B6D4',
        'sub_categories' => [
            ['emoji' => '✨', 'name' => 'Trips', 'default_time_type' => TimeType::Block->value],
            ['emoji' => '✈️', 'name' => 'Flights', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '🏨', 'name' => 'Hotels', 'default_time_type' => TimeType::Window->value],
            ['emoji' => '🚄', 'name' => 'Booked Trains', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '🚌', 'name' => 'Coaches', 'default_time_type' => TimeType::Exact->value],
        ],
    ],

    [
        'name' => 'Health',
        'colour' => '#EF4444',
        'sub_categories' => [
            ['emoji' => '🩺', 'name' => 'Doctors', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '🏥', 'name' => 'Hospital & Specialists', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '😬', 'name' => 'Dentist', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '👓', 'name' => 'Optician', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '🦴', 'name' => 'Physio', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '💊', 'name' => 'Prescriptions', 'default_time_type' => TimeType::Approximate->value],
        ],
    ],

    [
        'name' => 'Active',
        'colour' => '#84CC16',
        'sub_categories' => [
            ['emoji' => '🏋️', 'name' => 'Gym', 'default_time_type' => TimeType::Approximate->value],
            ['emoji' => '🧘', 'name' => 'Classes', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '🎽', 'name' => 'Playing Sports', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '🏃', 'name' => 'Running', 'default_time_type' => TimeType::Approximate->value],
            ['emoji' => '🥾', 'name' => 'Leisure Walks', 'default_time_type' => TimeType::Approximate->value],
        ],
    ],

    [
        'name' => 'Social',
        'colour' => '#EC4899',
        'sub_categories' => [
            ['emoji' => '👯', 'name' => 'Seeing Friends', 'default_time_type' => TimeType::Block->value],
            ['emoji' => '👨‍👩‍👧', 'name' => 'Family', 'default_time_type' => TimeType::Block->value],
            ['emoji' => '👋', 'name' => 'Friends Visiting', 'default_time_type' => TimeType::Block->value],
            ['emoji' => '🛏️', 'name' => 'Houseguests', 'default_time_type' => TimeType::Block->value],
            ['emoji' => '🎉', 'name' => 'Parties', 'default_time_type' => TimeType::Approximate->value],
            ['emoji' => '💒', 'name' => 'Weddings', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '🎂', 'name' => 'Birthdays', 'default_time_type' => TimeType::Block->value],
            ['emoji' => '💍', 'name' => 'Anniversaries', 'default_time_type' => TimeType::Block->value],
        ],
    ],

    [
        'name' => 'Food & Drink',
        'colour' => '#F97316',
        'sub_categories' => [
            ['emoji' => '🍽️', 'name' => 'Restaurants', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '☕', 'name' => 'Cafes', 'default_time_type' => TimeType::Approximate->value],
            ['emoji' => '🍸', 'name' => 'Bars', 'default_time_type' => TimeType::Approximate->value],
        ],
    ],

    [
        'name' => 'Going Out',
        'colour' => '#8B5CF6',
        'sub_categories' => [
            ['emoji' => '🎧', 'name' => 'Sets', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '🎪', 'name' => 'Festivals', 'default_time_type' => TimeType::Block->value],
            ['emoji' => '🏟️', 'name' => 'Live Sport', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '🎭', 'name' => 'Theatre', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '🍿', 'name' => 'Cinema', 'default_time_type' => TimeType::Exact->value],
        ],
    ],

    [
        'name' => 'Downtime',
        'colour' => '#EAB308',
        'sub_categories' => [
            ['emoji' => '📺', 'name' => 'Watching', 'default_time_type' => TimeType::Exact->value],
            ['emoji' => '🎨', 'name' => 'Hobbies', 'default_time_type' => TimeType::Approximate->value],
            ['emoji' => '📚', 'name' => 'Learning', 'default_time_type' => TimeType::Block->value],
            ['emoji' => '💻', 'name' => 'Side Projects', 'default_time_type' => TimeType::Block->value],
        ],
    ],

    [
        'name' => 'Home & Errands',
        'colour' => '#92400E',
        'sub_categories' => [
            ['emoji' => '🛍️', 'name' => 'Shopping', 'default_time_type' => TimeType::Approximate->value],
            ['emoji' => '🛒', 'name' => 'Groceries', 'default_time_type' => TimeType::Approximate->value],
            ['emoji' => '📦', 'name' => 'Deliveries', 'default_time_type' => TimeType::Window->value],
            ['emoji' => '🔧', 'name' => 'Tradespeople', 'default_time_type' => TimeType::Window->value],
            ['emoji' => '🧹', 'name' => 'Chores', 'default_time_type' => TimeType::Approximate->value],
            ['emoji' => '💇', 'name' => 'Haircut & Beauty', 'default_time_type' => TimeType::Exact->value],
        ],
    ],

    [
        'name' => 'Money',
        'colour' => '#22C55E',
        'sub_categories' => [
            ['emoji' => '💸', 'name' => 'Bills', 'default_time_type' => TimeType::Block->value],
            ['emoji' => '🤑', 'name' => 'Pay Day', 'default_time_type' => TimeType::Block->value],
            ['emoji' => '🔁', 'name' => 'Renewals', 'default_time_type' => TimeType::Block->value],
        ],
    ],

];
