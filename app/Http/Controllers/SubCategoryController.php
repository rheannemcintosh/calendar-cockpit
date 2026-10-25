<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubCategoryRequest;
use App\Http\Requests\UpdateSubCategoryRequest;
use App\Models\SubCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubCategoryController extends Controller
{
    /**
     * Show the form with the user's categories in alphabetical order, and the
     * category from the `category` query already chosen when it is theirs.
     */
    public function create(Request $request): Response
    {
        $categories = $request->user()->categories()
            ->orderByRaw('lower(name)')
            ->orderBy('id')
            ->get(['id', 'name', 'colour']);

        return Inertia::render('sub-categories/Create', [
            'categories' => $categories,
            'categoryId' => $categories->firstWhere('id', $request->integer('category'))?->id,
        ]);
    }

    public function store(StoreSubCategoryRequest $request): RedirectResponse
    {
        $request->category()->subCategories()->create($request->subCategoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sub-category created.')]);

        return to_route('categories.index');
    }

    /**
     * Show the form with the sub-category's current emoji and name, and the
     * category it is in, which cannot be changed.
     */
    public function edit(SubCategory $subCategory): Response
    {
        return Inertia::render('sub-categories/Edit', [
            'subCategory' => $subCategory->only(['id', 'emoji', 'name']),
            'category' => $subCategory->category->only(['id', 'name', 'colour']),
        ]);
    }

    public function update(UpdateSubCategoryRequest $request, SubCategory $subCategory): RedirectResponse
    {
        $subCategory->update($request->subCategoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Sub-category updated.')]);

        return to_route('categories.index');
    }
}
