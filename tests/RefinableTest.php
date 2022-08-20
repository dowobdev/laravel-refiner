<?php

use Dowob\Refiner\Refiner;
use Dowob\Refiner\Tests\TestClasses\Models\TestModel;

test('can determine refiner name from model with default config', function () {
    expect(Refiner::defaultRefinerFor(TestModel::class))->toBe('\\App\\Refiners\\TestModelRefiner');
});

test('can determine refiner name from model with custom config', function () {
    config(['refiner.namespace' => '\\Custom\\Refiners']);
    expect(Refiner::defaultRefinerFor(TestModel::class))->toBe('\\Custom\\Refiners\\TestModelRefiner');
});
