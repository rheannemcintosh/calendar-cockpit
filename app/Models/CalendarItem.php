<?php

namespace App\Models;

use App\Enums\TimeType;
use Carbon\CarbonImmutable;
use Database\Factories\CalendarItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $sub_category_id
 * @property string $title
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property TimeType $time_type
 * @property bool $time_type_overridden
 * @property string|null $location_name
 * @property string|null $location_address
 * @property float|null $location_latitude
 * @property float|null $location_longitude
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['title', 'starts_at', 'ends_at', 'sub_category_id', 'time_type', 'time_type_overridden', 'location_name', 'location_address', 'location_latitude', 'location_longitude'])]
class CalendarItem extends Model
{
    /** @use HasFactory<CalendarItemFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'time_type' => TimeType::Exact->value,
        'time_type_overridden' => false,
    ];

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
            'location_latitude' => 'float',
            'location_longitude' => 'float',
        ];
    }
}
