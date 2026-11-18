<?php

namespace App\Http\Requests;

use App\Enums\FlagIcon;
use App\Models\Flag;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFlagRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', $this->uniqueForUser(...)],
            'icon' => ['required', Rule::enum(FlagIcon::class)],
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
            'icon.required' => __('Please choose an icon.'),
            'icon.enum' => __('Please choose an icon again.'),
        ];
    }

    /**
     * The flag to store.
     *
     * @return array{name: string, icon: FlagIcon}
     */
    public function flagAttributes(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'icon' => FlagIcon::from($this->string('icon')->toString()),
        ];
    }

    /**
     * The user's flags that the name must not match.
     *
     * @return HasMany<Flag, User>
     */
    protected function otherFlags(): HasMany
    {
        return $this->user()->flags();
    }

    /**
     * Fail when the user already has a flag with this name, ignoring upper
     * and lower case. Spaces at either end are already trimmed.
     */
    private function uniqueForUser(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $exists = $this->otherFlags()
            ->whereRaw('lower(name) = ?', [mb_strtolower($value)])
            ->exists();

        if ($exists) {
            $fail(__('You already have a flag with that name.'));
        }
    }
}
