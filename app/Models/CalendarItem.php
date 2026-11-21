<?php

namespace App\Models;

use App\Enums\Firmness;
use App\Enums\TimeType;
use Carbon\CarbonImmutable;
use Database\Factories\CalendarItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
 * @property int $minutes_before
 * @property int $minutes_after
 * @property string|null $location_name
 * @property string|null $location_address
 * @property float|null $location_latitude
 * @property float|null $location_longitude
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'starts_at', 'ends_at', 'sub_category_id', 'time_type', 'time_type_overridden', 'firmness', 'all_day', 'minutes_before', 'minutes_after', 'location_name', 'location_address', 'location_latitude', 'location_longitude'])]
class CalendarItem extends Model
{
    /**
     * The most time, in minutes, that can be added before or after a calendar item.
     */
    public const MAX_EXTRA_MINUTES = 720;

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
        'minutes_before' => 0,
        'minutes_after' => 0,
    ];

    /**
     * Log every change to the calendar item's own fields, leaving out the timestamps.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('calendar_item')
            ->logOnly(['title', 'starts_at', 'ends_at', 'all_day', 'minutes_before', 'minutes_after', 'sub_category_id', 'time_type', 'time_type_overridden', 'firmness', 'location_name', 'location_address', 'location_latitude', 'location_longitude'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * When the calendar item's time starts, with the time added before it.
     */
    public function startsWithTimeBefore(): CarbonImmutable
    {
        return $this->starts_at->subMinutes($this->minutes_before);
    }

    /**
     * When the calendar item's time ends, with the time added after it.
     */
    public function endsWithTimeAfter(): CarbonImmutable
    {
        return $this->ends_at->addMinutes($this->minutes_after);
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
            'minutes_before' => 'integer',
            'minutes_after' => 'integer',
            'location_latitude' => 'float',
            'location_longitude' => 'float',
        ];
    }
}
