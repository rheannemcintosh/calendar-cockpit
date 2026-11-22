<?php

namespace App\Support;

use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

/**
 * Asks TypeSafe AI's Jev model which of a user's sub-categories a calendar
 * item fits best, with one choice question over their sub-categories.
 */
class SubCategorySuggester
{
    /**
     * The option Jev picks when none of the sub-categories fit the title.
     */
    public const NONE = 'none';

    /**
     * A choice question takes at most 255 options, and one is "none".
     */
    public const MAX_SUB_CATEGORIES = 254;

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
     * Suggest which of the user's sub-categories a calendar item's title
     * belongs in. Returns null, rather than failing, when there is no API
     * key, the user has no sub-categories, Jev errors or times out, or Jev is
     * not confident enough, so the forms carry on as normal without a
     * suggestion.
     */
    public function suggest(User $user, string $title): ?SubCategory
    {
        if (! self::isEnabled()) {
            Log::debug('No TypeSafe API key is set, so no sub-category is suggested. Set TYPESAFE_API_KEY.');

            return null;
        }

        $categories = $user->categories()
            ->whereHas('subCategories')
            ->with(['subCategories' => fn (HasMany $query) => $query->orderByRaw('lower(name)')->orderBy('id')])
            ->orderByRaw('lower(name)')
            ->orderBy('id')
            ->get();

        /** @var array<int, SubCategory> $subCategories */
        $subCategories = [];

        /** @var array<int, array{category: string, sub_category: string}> $criteria */
        $criteria = [];

        foreach ($categories as $category) {
            foreach ($category->subCategories as $subCategory) {
                if (count($criteria) === self::MAX_SUB_CATEGORIES) {
                    break 2;
                }

                $subCategories[$subCategory->id] = $subCategory;
                $criteria[$subCategory->id] = [
                    'category' => $category->name,
                    'sub_category' => $subCategory->name,
                ];
            }
        }

        if ($criteria === []) {
            return null;
        }

        $answer = $this->jev->choose(
            'sub_category',
            ['calendar_item' => $title],
            'Which of these sub-categories in a personal calendar does this calendar item belong in, given its title? Each option is one of the user\'s sub-categories, with the category it is in. Choose "none" if the calendar item does not fit any of them, or the title is not meaningful.',
            $criteria + [
                self::NONE => 'None of these sub-categories fit the calendar item, or the title is not meaningful',
            ],
        );

        if ($answer === null || ! ctype_digit($answer['choice'])) {
            return null;
        }

        $subCategory = $subCategories[(int) $answer['choice']] ?? null;

        if ($subCategory === null || $answer['confidence'] < config()->float('services.typesafe.sub_category_suggestion_confidence')) {
            return null;
        }

        return $subCategory->withoutRelations();
    }
}
