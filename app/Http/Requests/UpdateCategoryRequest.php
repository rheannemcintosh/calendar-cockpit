<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The same checks as creating a category, except that keeping the category's
 * own name does not count as a duplicate.
 */
class UpdateCategoryRequest extends StoreCategoryRequest
{
    /**
     * The category being changed.
     */
    public function category(): Category
    {
        /** @var Category */
        return $this->route('category');
    }

    /**
     * @return HasMany<Category, User>
     */
    protected function otherCategories(): HasMany
    {
        return parent::otherCategories()->whereKeyNot($this->category()->id);
    }
}
