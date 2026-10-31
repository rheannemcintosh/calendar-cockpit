<?php

namespace App\Enums;

/**
 * How precise a calendar item's start and end times are.
 */
enum TimeType: string
{
    case Exact = 'exact';
    case Window = 'window';
    case Block = 'block';
    case Approximate = 'approximate';

    public function label(): string
    {
        return match ($this) {
            self::Exact => __('Exact'),
            self::Window => __('Window'),
            self::Block => __('Block'),
            self::Approximate => __('Approximate'),
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::Exact => __('Starts and ends at these times.'),
            self::Window => __('A booked slot. It could happen any time in it.'),
            self::Block => __('Time set aside for this.'),
            self::Approximate => __('Roughly these times.'),
        };
    }

    /**
     * Every time type with its label and hint, in order, for a form to choose from.
     *
     * @return list<array{value: string, label: string, hint: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $timeType): array => [
            'value' => $timeType->value,
            'label' => $timeType->label(),
            'hint' => $timeType->hint(),
        ], self::cases());
    }
}
