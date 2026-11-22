<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\SubCategory;
use App\Support\CategoryHistory;
use App\Support\Timestamps;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Show the user's categories with the sub-categories in each one, both in
     * alphabetical order, and how many calendar items each one holds so that
     * deleting it can say how many will become uncategorised.
     */
    public function index(Request $request): Response
    {
        $categories = $request->user()->categories()
            ->with(['subCategories' => fn (HasMany $query) => $query
                ->withCount('calendarItems')
                ->orderByRaw('lower(name)')
                ->orderBy('id'),
            ])
            ->withCount('calendarItems')
            ->orderByRaw('lower(name)')
            ->orderBy('id')
            ->get();

        return Inertia::render('categories/Index', [
            'categories' => $categories->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'colour' => $category->colour,
                'calendarItemsCount' => (int) $category->calendar_items_count,
                'subCategories' => $category->subCategories->map(fn (SubCategory $subCategory): array => [
                    'id' => $subCategory->id,
                    'emoji' => $subCategory->emoji,
                    'name' => $subCategory->name,
                    'defaultTimeTypeLabel' => $subCategory->default_time_type->label(),
                    'calendarItemsCount' => (int) $subCategory->calendar_items_count,
                ]),
            ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('categories/Create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $request->user()->categories()->create($request->categoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return to_route('categories.index');
    }

    /**
     * Show the form with the category's current name and colour, and when it
     * was created and last updated in the user's timezone.
     */
    public function edit(Request $request, Category $category): Response
    {
        return Inertia::render('categories/Edit', [
            'category' => $category->only(['id', 'name', 'colour']),
            'timestamps' => Timestamps::of($category, $request->user()->localTimezone()),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->categoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return to_route('categories.index');
    }

    /**
     * Show each change made to the category, newest first, with when it was
     * made in the user's timezone.
     */
    public function history(Request $request, Category $category): Response
    {
        return Inertia::render('categories/History', [
            'category' => $category->only(['id', 'name']),
            'entries' => CategoryHistory::of($category, $request->user()->localTimezone()),
        ]);
    }

    /**
     * Delete the category and its sub-categories, keeping their calendar
     * items as uncategorised.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return to_route('categories.index');
    }
}
