<?php

namespace App\Http\Requests;

use App\Enums\TimeType;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

/**
 * The same checks as creating a sub-category, except that its category cannot
 * be changed and keeping its own name does not count as a duplicate.
 */
class UpdateSubCategoryRequest extends StoreSubCategoryRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'emoji' => ['required', 'string', $this->singleEmoji(...)],
            'name' => ['required', 'string', 'max:50', $this->uniqueInCategory(...)],
            'default_time_type' => ['required', Rule::enum(TimeType::class)],
        ];
    }

    /**
     * The sub-category being changed.
     */
    public function subCategory(): SubCategory
    {
        /** @var SubCategory */
        return $this->route('sub_category');
    }

    /**
     * @return HasMany<SubCategory, Category>
     */
    protected function otherSubCategories(): HasMany
    {
        return $this->subCategory()->category->subCategories()->whereKeyNot($this->subCategory()->id);
    }
}
