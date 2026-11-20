<?php

namespace App\Http\Controllers;

use App\Actions\CreateStarterCategories;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StarterCategoryController extends Controller
{
    /**
     * Add the starter categories and their sub-categories for a user who
     * has no categories. The action checks again, so a user who already
     * has one, for example from another tab, gets nothing added.
     */
    public function __invoke(Request $request, CreateStarterCategories $createStarterCategories): RedirectResponse
    {
        $created = $createStarterCategories->handle($request->user());

        Inertia::flash('toast', $created->isEmpty()
            ? ['type' => 'info', 'message' => __('You already have categories, so no starter categories were added.')]
            : ['type' => 'success', 'message' => __('Starter categories added.')]);

        return to_route('categories.index');
    }
}
