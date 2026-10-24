<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Show the user's categories with the sub-categories in each one, both in
     * alphabetical order.
     */
    public function index(Request $request): Response
    {
        $categories = $request->user()->categories()
            ->with(['subCategories' => fn (HasMany $query) => $query->orderByRaw('lower(name)')->orderBy('id')])
            ->orderByRaw('lower(name)')
            ->orderBy('id')
            ->get();

        return Inertia::render('categories/Index', [
            'categories' => $categories->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'colour' => $category->colour,
                'subCategories' => $category->subCategories->map(fn (SubCategory $subCategory): array => [
                    'id' => $subCategory->id,
                    'emoji' => $subCategory->emoji,
                    'name' => $subCategory->name,
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

    public function edit(Category $category): Response
    {
        return Inertia::render('categories/Edit', [
            'category' => $category->only(['id', 'name', 'colour']),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->categoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return to_route('categories.index');
    }
}
