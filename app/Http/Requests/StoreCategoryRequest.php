<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', $this->uniqueForUser(...)],
            'colour' => ['required', 'string', 'regex:/^#?[0-9A-Fa-f]{6}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('Please enter a name.'),
            'name.max' => __('The name cannot be longer than 50 characters.'),
            'colour.required' => __('Please choose a colour.'),
            'colour.regex' => __('Please enter a valid hex code, such as #3B82F6.'),
        ];
    }

    /**
     * The category to store, with the colour always starting with a #.
     *
     * @return array{name: string, colour: string}
     */
    public function categoryAttributes(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'colour' => '#'.ltrim($this->string('colour')->toString(), '#'),
        ];
    }

    /**
     * The user's categories that the name must not match.
     *
     * @return HasMany<Category, User>
     */
    protected function otherCategories(): HasMany
    {
        return $this->user()->categories();
    }

    /**
     * Fail when the user already has a category with this name, ignoring
     * upper and lower case. Spaces at either end are already trimmed.
     */
    private function uniqueForUser(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $exists = $this->otherCategories()
            ->whereRaw('lower(name) = ?', [mb_strtolower($value)])
            ->exists();

        if ($exists) {
            $fail(__('You already have a category with that name.'));
        }
    }
}
