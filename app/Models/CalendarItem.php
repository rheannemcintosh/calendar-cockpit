<?php

namespace App\Models;

use App\Enums\Firmness;
use App\Enums\TimeType;
use Carbon\CarbonImmutable;
use Database\Factories\CalendarItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $sub_category_id
 * @property string $title
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property TimeType $time_type
 * @property bool $time_type_overridden
 * @property Firmness $firmness
 * @property bool $all_day
 * @property string|null $location_name
 * @property string|null $location_address
 * @property float|null $location_latitude
 * @property float|null $location_longitude
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'starts_at', 'ends_at', 'sub_category_id', 'time_type', 'time_type_overridden', 'firmness', 'all_day', 'location_name', 'location_address', 'location_latitude', 'location_longitude'])]
class CalendarItem extends Model
{
    /** @use HasFactory<CalendarItemFactory> */
    use HasFactory;

    use LogsActivity;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'time_type' => TimeType::Exact->value,
        'time_type_overridden' => false,
        'firmness' => Firmness::Confirmed->value,
        'all_day' => false,
    ];

    /**
     * Log every change to the calendar item's own fields, leaving out the timestamps.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('calendar_item')
            ->logOnly(['title', 'starts_at', 'ends_at', 'all_day', 'sub_category_id', 'time_type', 'time_type_overridden', 'firmness', 'location_name', 'location_address', 'location_latitude', 'location_longitude'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Leave the given calendar items uncategorised because their category or
     * sub-category is being deleted. One bulk query skips the activity log, so
     * each calendar item first logs its own change, saying what was deleted.
     *
     * @param  Builder<self>  $query
     * @param  'category'|'sub_category'  $type
     */
    public static function uncategorise(Builder $query, string $type, string $name): void
    {
        $query->clone()->with('subCategory:id,name')->lazyById()->each(function (self $calendarItem) use ($type, $name): void {
            activity('calendar_item')
                ->performedOn($calendarItem)
                ->event('updated')
                ->withChanges([
                    'attributes' => ['sub_category_id' => null],
                    'old' => ['sub_category_id' => $calendarItem->sub_category_id],
                ])
                ->withProperties([
                    'deleted' => ['type' => $type, 'name' => $name],
                    'sub_category_name' => $calendarItem->subCategory?->name,
                ])
                ->log('updated');
        });

        $query->update(['sub_category_id' => null]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<SubCategory, $this>
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    /**
     * The calendar item's flags, always in the order the user's flags are in.
     *
     * @return BelongsToMany<Flag, $this>
     */
    public function flags(): BelongsToMany
    {
        return $this->belongsToMany(Flag::class)->orderBy('flags.id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'sub_category_id' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'time_type' => TimeType::class,
            'time_type_overridden' => 'boolean',
            'firmness' => Firmness::class,
            'all_day' => 'boolean',
            'location_latitude' => 'float',
            'location_longitude' => 'float',
        ];
    }
}
