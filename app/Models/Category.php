<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $colour
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'colour'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<SubCategory, $this>
     */
    public function subCategories(): HasMany
    {
        return $this->hasMany(SubCategory::class);
    }

    /**
     * Leave the calendar items in this category uncategorised, since the
     * database cannot do it on delete, then delete it and its sub-categories.
     */
    public function delete(): ?bool
    {
        return DB::transaction(function (): ?bool {
            CalendarItem::query()
                ->whereIn('sub_category_id', $this->subCategories()->select('id'))
                ->update(['sub_category_id' => null]);

            return parent::delete();
        });
    }

    /**
     * @return Attribute<string, string>
     */
    protected function colour(): Attribute
    {
        return Attribute::set(fn (string $value): string => strtoupper($value));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }
}
