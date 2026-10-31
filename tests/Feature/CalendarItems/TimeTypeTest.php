<?php

use App\Enums\TimeType;

test('the time types are exact, window, block and approximate', function () {
    expect(array_map(fn (TimeType $timeType) => $timeType->value, TimeType::cases()))
        ->toBe(['exact', 'window', 'block', 'approximate']);
});

test('each time type has a label and a hint', function (TimeType $timeType) {
    expect($timeType->label())->toBeString()->not->toBeEmpty()
        ->and($timeType->hint())->toBeString()->not->toBeEmpty();
})->with(TimeType::cases());

test('the time types have distinct labels and hints', function () {
    $labels = array_map(fn (TimeType $timeType) => $timeType->label(), TimeType::cases());
    $hints = array_map(fn (TimeType $timeType) => $timeType->hint(), TimeType::cases());

    expect(array_unique($labels))->toHaveCount(4)
        ->and(array_unique($hints))->toHaveCount(4);
});

test('the options list every time type in order with its label and hint', function () {
    expect(TimeType::options())->toBe(array_map(fn (TimeType $timeType) => [
        'value' => $timeType->value,
        'label' => $timeType->label(),
        'hint' => $timeType->hint(),
    ], TimeType::cases()));
});
