<?php

namespace App\Enums;

/**
 * How settled a calendar item is: booked, still being worked out, or just an idea.
 */
enum Firmness: string
{
    case Confirmed = 'confirmed';
    case Tentative = 'tentative';
    case Idea = 'idea';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => __('Confirmed'),
            self::Tentative => __('Tentative'),
            self::Idea => __('Idea'),
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Confirmed => __('This is happening.'),
            self::Tentative => __('Planned, but it could still change.'),
            self::Idea => __('Something you might do.'),
        };
    }

    /**
     * Every firmness with its label and hint, in order, for a form to choose from.
     *
     * @return list<array{value: string, label: string, hint: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $firmness): array => [
            'value' => $firmness->value,
            'label' => $firmness->label(),
            'hint' => $firmness->hint(),
        ], self::cases());
    }
}
