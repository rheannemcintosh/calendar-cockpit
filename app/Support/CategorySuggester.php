<?php

namespace App\Support;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

/**
 * Asks TypeSafe AI's Jev model which of a user's categories a sub-category
 * fits best, with one choice question over their categories.
 */
class CategorySuggester
{
    /**
     * The option Jev picks when none of the categories fit the name.
     */
    public const NONE = 'none';

    /**
     * A choice question takes at most 255 options, and one is "none".
     */
    public const MAX_CATEGORIES = 254;

    /**
     * How many of each category's sub-categories are sent as examples of
     * what is in it.
     */
    public const MAX_SUB_CATEGORIES = 30;

    public function __construct(private readonly Jev $jev) {}

    /**
     * Whether a TypeSafe API key is set, so the forms only ask for
     * suggestions when there can be one.
     */
    public static function isEnabled(): bool
    {
        return Jev::isEnabled();
    }

    /**
     * Suggest which of the user's categories a sub-category's name belongs
     * in. Returns null, rather than failing, when there is no API key, the
     * user has no categories, Jev errors or times out, or Jev is not
     * confident enough, so the forms carry on as normal without a suggestion.
     */
    public function suggest(User $user, string $name): ?Category
    {
        if (! self::isEnabled()) {
            Log::debug('No TypeSafe API key is set, so no category is suggested. Set TYPESAFE_API_KEY.');

            return null;
        }

        $categories = $user->categories()
            ->with(['subCategories' => fn (HasMany $query) => $query
                ->orderByRaw('lower(name)')
                ->orderBy('id')
                ->limit(self::MAX_SUB_CATEGORIES)])
            ->orderByRaw('lower(name)')
            ->orderBy('id')
            ->limit(self::MAX_CATEGORIES)
            ->get()
            ->keyBy('id');

        if ($categories->isEmpty()) {
            return null;
        }

        $answer = $this->jev->choose(
            'category',
            ['sub_category' => $name],
            'Which of these categories in a personal calendar does this new sub-category belong in, given its name? Each option is one of the user\'s categories, with the sub-categories already in it. Choose "none" if the sub-category does not fit any of them, or the name is not meaningful.',
            $categories->map(fn (Category $category): array => [
                'category' => $category->name,
                'sub_categories' => $category->subCategories
                    ->map(fn (SubCategory $subCategory): string => $subCategory->name)
                    ->all(),
            ])->all() + [
                self::NONE => 'None of these categories fit the sub-category, or the name is not meaningful',
            ],
        );

        if ($answer === null || ! ctype_digit($answer['choice'])) {
            return null;
        }

        $category = $categories->get((int) $answer['choice']);

        if ($category === null || $answer['confidence'] < config()->float('services.typesafe.category_suggestion_confidence')) {
            return null;
        }

        return $category->withoutRelations();
    }
}
