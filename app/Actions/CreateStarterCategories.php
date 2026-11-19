<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CreateStarterCategories
{
    /**
     * Give the user the starter categories and their sub-categories, from
     * config/starter-categories.php, unless they already have a category.
     *
     * It all happens in one transaction, so a failure leaves the user with
     * no categories rather than half a set. The user's row is locked first,
     * so two requests at once cannot both find no categories and create
     * the set twice.
     *
     * @return Collection<int, Category> the categories created, or none when the user already had some
     */
    public function handle(User $user): Collection
    {
        return DB::transaction(function () use ($user): Collection {
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            if ($user->categories()->exists()) {
                return new Collection;
            }

            /** @var list<array{name: string, colour: string, sub_categories: list<array{emoji: string, name: string, default_time_type: string}>}> $starterCategories */
            $starterCategories = config('starter-categories');

            return new Collection(array_map(function (array $starter) use ($user): Category {
                $category = $user->categories()->create([
                    'name' => $starter['name'],
                    'colour' => $starter['colour'],
                ]);

                $category->subCategories()->createMany($starter['sub_categories']);

                return $category;
            }, $starterCategories));
        });
    }
}
