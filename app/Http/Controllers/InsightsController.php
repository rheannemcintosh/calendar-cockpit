<?php

namespace App\Http\Controllers;

use App\Models\CalendarItem;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InsightsController extends Controller
{
    /**
     * Show the user insights about their calendar: when their first category,
     * sub-category and calendar item were created, and when each category and
     * sub-category was created, oldest first, with the first calendar item
     * created in each one. Dates are in the user's timezone.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $timezone = $user->localTimezone();

        $categories = $user->categories()
            ->with(['subCategories' => fn (HasMany $query) => $query
                ->orderBy('created_at')
                ->orderBy('id'),
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $firstSubCategory = $categories
            ->flatMap(fn (Category $category) => $category->subCategories)
            ->sortBy([['created_at', 'asc'], ['id', 'asc']])
            ->first();

        $firstCategory = $categories->first();

        $firstCalendarItem = $user->calendarItems()
            ->orderBy('created_at')
            ->orderBy('id')
            ->first();

        // The first calendar item created in each sub-category, keyed by it.
        $firstCalendarItems = $user->calendarItems()
            ->whereNotNull('sub_category_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'title', 'sub_category_id', 'created_at'])
            ->unique('sub_category_id')
            ->keyBy('sub_category_id');

        // The day something was created, e.g. "21 Nov 2026".
        $created = fn (Model $model): string => $model->getAttribute('created_at')->setTimezone($timezone)->format('j M Y');

        $calendarItem = fn (?CalendarItem $calendarItem): ?array => $calendarItem === null ? null : [
            'title' => $calendarItem->title,
            'created' => $created($calendarItem),
        ];

        // The first calendar item created in any of the category's sub-categories.
        $firstInCategory = fn (Category $category): ?CalendarItem => $category->subCategories
            ->map(fn (SubCategory $subCategory): ?CalendarItem => $firstCalendarItems->get($subCategory->id))
            ->filter()
            ->sortBy([['created_at', 'asc'], ['id', 'asc']])
            ->first();

        return Inertia::render('insights/Index', [
            'firsts' => [
                'category' => $firstCategory === null ? null : [
                    'name' => $firstCategory->name,
                    'colour' => $firstCategory->colour,
                    'created' => $created($firstCategory),
                ],
                'subCategory' => $firstSubCategory === null ? null : [
                    'emoji' => $firstSubCategory->emoji,
                    'name' => $firstSubCategory->name,
                    'categoryName' => $categories->firstWhere('id', $firstSubCategory->category_id)?->name,
                    'created' => $created($firstSubCategory),
                ],
                'calendarItem' => $calendarItem($firstCalendarItem),
            ],
            'categories' => $categories->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'colour' => $category->colour,
                'created' => $created($category),
                'firstCalendarItem' => $calendarItem($firstInCategory($category)),
                'subCategories' => $category->subCategories->map(fn (SubCategory $subCategory): array => [
                    'id' => $subCategory->id,
                    'emoji' => $subCategory->emoji,
                    'name' => $subCategory->name,
                    'created' => $created($subCategory),
                    'firstCalendarItem' => $calendarItem($firstCalendarItems->get($subCategory->id)),
                ]),
            ]),
        ]);
    }
}
