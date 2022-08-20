<?php

use Dowob\Refiner\Definitions\Definition;
use Dowob\Refiner\Filtering\ClosureFilter;
use Dowob\Refiner\Tests\TestClasses\Models\TestModel;

test('cannot use closure filter without closure being defined on definition', function () {
    $definition = Definition::make('test');

    expect(fn() => (new ClosureFilter($definition, 'test'))->apply(TestModel::query()))
        ->toThrow(BadMethodCallException::class);
});
