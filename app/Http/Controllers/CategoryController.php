<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('categories/Create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $category = $request->user()->categories()->create($request->categoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        // A category needs a sub-category before calendar items can use it.
        return to_route('sub-categories.create', ['category' => $category->id]);
    }
}
