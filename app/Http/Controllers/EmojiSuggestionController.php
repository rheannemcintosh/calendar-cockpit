<?php

namespace App\Http\Controllers;

use App\Support\EmojiSuggester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmojiSuggestionController extends Controller
{
    /**
     * Suggest an emoji for a sub-category's name, and its category when one
     * is chosen, or null when there is no suggestion.
     */
    public function __invoke(Request $request, EmojiSuggester $emojiSuggester): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', $request->user()->id),
            ],
        ], [
            'name.required' => __('Please enter a name.'),
            'name.max' => __('The name cannot be longer than 50 characters.'),
            'category_id.integer' => __('Please choose a category again.'),
            'category_id.exists' => __('Please choose a category again.'),
        ]);

        $category = $request->filled('category_id')
            ? $request->user()->categories()->find($request->integer('category_id'))
            : null;

        return response()->json([
            'emoji' => $emojiSuggester->suggest($request->string('name')->toString(), $category?->name),
        ]);
    }
}
