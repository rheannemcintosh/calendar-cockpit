<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\SubCategory;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubCategoryRequest extends FormRequest
{
    /**
     * The most bytes an emoji can take up, the size of the emoji column.
     */
    private const EMOJI_MAX_BYTES = 32;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', $this->user()->id),
            ],
            'emoji' => ['required', 'string', $this->singleEmoji(...)],
            'name' => ['required', 'string', 'max:50', $this->uniqueInCategory(...)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => __('Please choose a category.'),
            'category_id.integer' => __('Please choose a category again.'),
            'category_id.exists' => __('Please choose a category again.'),
            'emoji.required' => __('Please choose an emoji.'),
            'name.required' => __('Please enter a name.'),
            'name.max' => __('The name cannot be longer than 50 characters.'),
        ];
    }

    /**
     * The user's category to store the sub-category in.
     */
    public function category(): Category
    {
        return $this->user()->categories()->findOrFail($this->integer('category_id'));
    }

    /**
     * @return array{emoji: string, name: string}
     */
    public function subCategoryAttributes(): array
    {
        return [
            'emoji' => $this->string('emoji')->toString(),
            'name' => $this->string('name')->toString(),
        ];
    }

    /**
     * The sub-categories that the name must not match: those in the chosen
     * category, or null when the category is not the user's, which is left
     * to its own rule.
     *
     * @return HasMany<SubCategory, Category>|null
     */
    protected function otherSubCategories(): ?HasMany
    {
        return $this->user()->categories()->find($this->integer('category_id'))?->subCategories();
    }

    /**
     * Fail unless the value is exactly one emoji. An emoji with a skin tone,
     * a flag or a combined emoji such as 👩‍💻 is one character on screen,
     * so it counts as one.
     */
    protected function singleEmoji(string $attribute, mixed $value, Closure $fail): void
    {
        $isSingleEmoji = is_string($value)
            && strlen($value) <= self::EMOJI_MAX_BYTES
            && grapheme_strlen($value) === 1
            && preg_match('/[\p{Extended_Pictographic}\x{1F1E6}-\x{1F1FF}\x{20E3}]/u', $value) === 1;

        if (! $isSingleEmoji) {
            $fail(__('Please enter a single emoji.'));
        }
    }

    /**
     * Fail when the category already has a sub-category with this name,
     * ignoring upper and lower case. Spaces at either end are already trimmed.
     */
    protected function uniqueInCategory(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $exists = $this->otherSubCategories()
            ?->whereRaw('lower(name) = ?', [mb_strtolower($value)])
            ->exists();

        if ($exists) {
            $fail(__('This category already has a sub-category with that name.'));
        }
    }
}
