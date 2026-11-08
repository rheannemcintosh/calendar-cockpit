<?php

use App\Enums\Firmness;

test('the firmnesses are confirmed, tentative and idea', function () {
    expect(array_map(fn (Firmness $firmness) => $firmness->value, Firmness::cases()))
        ->toBe(['confirmed', 'tentative', 'idea']);
});

test('each firmness has a label and a hint', function (Firmness $firmness) {
    expect($firmness->label())->toBeString()->not->toBeEmpty()
        ->and($firmness->hint())->toBeString()->not->toBeEmpty();
})->with(Firmness::cases());

test('the firmnesses have distinct labels and hints', function () {
    $labels = array_map(fn (Firmness $firmness) => $firmness->label(), Firmness::cases());
    $hints = array_map(fn (Firmness $firmness) => $firmness->hint(), Firmness::cases());

    expect(array_unique($labels))->toHaveCount(3)
        ->and(array_unique($hints))->toHaveCount(3);
});

test('the options list every firmness in order with its label and hint', function () {
    expect(Firmness::options())->toBe(array_map(fn (Firmness $firmness) => [
        'value' => $firmness->value,
        'label' => $firmness->label(),
        'hint' => $firmness->hint(),
    ], Firmness::cases()));
});
