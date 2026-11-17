<?php

namespace App\Models;

use App\Enums\FlagIcon;
use Carbon\CarbonImmutable;
use Database\Factories\FlagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

/**
 * A kind of commitment, such as Pre-Paid, that calendar items can be flagged with.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property FlagIcon $icon
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['name', 'icon'])]
class Flag extends Model
{
    /** @use HasFactory<FlagFactory> */
    use HasFactory;

    /**
     * The flags every user starts with, in the order they are shown.
     *
     * @var list<array{name: string, icon: FlagIcon}>
     */
    public const DEFAULTS = [
        ['name' => 'Pre-Paid', 'icon' => FlagIcon::PoundSterling],
        ['name' => 'Non-Cancellable', 'icon' => FlagIcon::Lock],
        ['name' => 'Important', 'icon' => FlagIcon::Star],
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<CalendarItem, $this>
     */
    public function calendarItems(): BelongsToMany
    {
        return $this->belongsToMany(CalendarItem::class);
    }

    /**
     * Take this flag off its calendar items, since the database cannot do it
     * on delete, then delete it.
     */
    public function delete(): ?bool
    {
        return DB::transaction(function (): ?bool {
            $this->calendarItems()->detach();

            return parent::delete();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'icon' => FlagIcon::class,
        ];
    }
}
