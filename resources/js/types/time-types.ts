// The values of App\Enums\TimeType.
export type TimeTypeValue = 'exact' | 'window' | 'block' | 'approximate';

// A time type as offered on a form, from App\Enums\TimeType::options().
export type TimeTypeOption = {
    value: TimeTypeValue;
    label: string;
    hint: string;
};
