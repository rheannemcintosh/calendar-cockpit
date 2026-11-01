<?php

namespace App\Http\Controllers;

use App\Support\CategorySuggester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategorySuggestionController extends Controller
{
    /**
     * Suggest which of the user's categories a sub-category's name belongs
     * in, or null when there is no suggestion.
     */
    public function __invoke(Request $request, CategorySuggester $categorySuggester): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50'],
        ], [
            'name.required' => __('Please enter a name.'),
            'name.max' => __('The name cannot be longer than 50 characters.'),
        ]);

        return response()->json([
            'category_id' => $categorySuggester->suggest($request->user(), $request->string('name')->toString())?->id,
        ]);
    }
}
