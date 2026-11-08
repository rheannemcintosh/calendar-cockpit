<?php

namespace App\Enums;

/**
 * The icons a flag can show, each the kebab-case name of a Lucide icon.
 */
enum FlagIcon: string
{
    case PoundSterling = 'pound-sterling';
    case Lock = 'lock';
    case Star = 'star';
    case Heart = 'heart';
    case Bell = 'bell';
    case Flag = 'flag';
    case CircleAlert = 'circle-alert';
    case Clock = 'clock';
    case CalendarCheck = 'calendar-check';
    case Ticket = 'ticket';
    case Plane = 'plane';
    case Gift = 'gift';
    case Users = 'users';
    case Briefcase = 'briefcase';
    case House = 'house';
    case Car = 'car';
    case Handshake = 'handshake';
    case CreditCard = 'credit-card';
    case Receipt = 'receipt';
    case Shield = 'shield';
    case Sparkles = 'sparkles';
    case Zap = 'zap';
    case Pin = 'pin';
    case Bookmark = 'bookmark';

    public function label(): string
    {
        return match ($this) {
            self::PoundSterling => __('Pound sign'),
            self::Lock => __('Lock'),
            self::Star => __('Star'),
            self::Heart => __('Heart'),
            self::Bell => __('Bell'),
            self::Flag => __('Flag'),
            self::CircleAlert => __('Alert'),
            self::Clock => __('Clock'),
            self::CalendarCheck => __('Calendar with a tick'),
            self::Ticket => __('Ticket'),
            self::Plane => __('Plane'),
            self::Gift => __('Gift'),
            self::Users => __('People'),
            self::Briefcase => __('Briefcase'),
            self::House => __('House'),
            self::Car => __('Car'),
            self::Handshake => __('Handshake'),
            self::CreditCard => __('Bank card'),
            self::Receipt => __('Receipt'),
            self::Shield => __('Shield'),
            self::Sparkles => __('Sparkles'),
            self::Zap => __('Lightning bolt'),
            self::Pin => __('Pin'),
            self::Bookmark => __('Bookmark'),
        };
    }

    /**
     * Every icon with its label, in order, for a form to choose from.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $icon): array => [
            'value' => $icon->value,
            'label' => $icon->label(),
        ], self::cases());
    }
}
