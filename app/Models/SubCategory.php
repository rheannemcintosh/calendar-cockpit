<?php

namespace App\Models;

use App\Enums\TimeType;
use Carbon\CarbonImmutable;
use Database\Factories\SubCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $category_id
 * @property string $emoji
 * @property string $name
 * @property TimeType $default_time_type
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['emoji', 'name', 'default_time_type'])]
class SubCategory extends Model
{
    /** @use HasFactory<SubCategoryFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'default_time_type' => TimeType::Exact->value,
    ];

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<CalendarItem, $this>
     */
    public function calendarItems(): HasMany
    {
        return $this->hasMany(CalendarItem::class);
    }

    /**
     * Leave this sub-category's calendar items uncategorised, since the
     * database cannot do it on delete, then delete it.
     */
    public function delete(): ?bool
    {
        return DB::transaction(function (): ?bool {
            $this->calendarItems()->update(['sub_category_id' => null]);

            return parent::delete();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category_id' => 'integer',
            'default_time_type' => TimeType::class,
        ];
    }
}
