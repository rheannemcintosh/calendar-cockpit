<?php

namespace App\Http\Controllers;

use App\Support\SubCategorySuggester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubCategorySuggestionController extends Controller
{
    /**
     * Suggest which of the user's sub-categories a calendar item's title
     * belongs in, or null when there is no suggestion.
     */
    public function __invoke(Request $request, SubCategorySuggester $subCategorySuggester): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ], [
            'title.required' => __('Please enter a title.'),
            'title.max' => __('The title cannot be longer than 255 characters.'),
        ]);

        return response()->json([
            'sub_category_id' => $subCategorySuggester->suggest($request->user(), $request->string('title')->toString())?->id,
        ]);
    }
}
