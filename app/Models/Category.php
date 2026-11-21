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
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

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

    use LogsActivity;

    /**
     * Log every change to the category's own fields, leaving out the timestamps.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('category')
            ->logOnly(['name', 'colour'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

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
     * The calendar items in this category, through their sub-categories.
     *
     * @return HasManyThrough<CalendarItem, SubCategory, $this>
     */
    public function calendarItems(): HasManyThrough
    {
        return $this->hasManyThrough(CalendarItem::class, SubCategory::class);
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
