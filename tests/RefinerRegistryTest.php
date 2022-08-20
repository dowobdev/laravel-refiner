<?php

use Dowob\Refiner\Facades\Registry as RefinerRegistryFacade;
use Dowob\Refiner\Registry;
use Dowob\Refiner\Tests\TestClasses\Models\TestModel;
use Dowob\Refiner\Tests\TestClasses\Refiners\TestModelRefiner;
use Dowob\Refiner\Tests\TestClasses\Refiners\TestModelRefinerWithDefaultSort;
use Dowob\Refiner\Tests\TestClasses\Refiners\TestModelRefinerWithSearchAlwaysRan;

beforeEach(function () {
    config(['refiner.namespace' => '\\Dowob\\Refiner\\Tests\\TestClasses\\Refiners']);
    $this->registry = new Registry;
});

test('returns null if nothing in registry', function () {
    expect($this->registry->shift())->toBeNull()
        ->and($this->registry->pop())->toBeNull();
});

test('can get first refiner from registry', function () {
    $this->registry
        ->push(new TestModelRefiner)
        ->push(new TestModelRefinerWithDefaultSort);

    expect(get_class($this->registry->shift()))->toBe(TestModelRefiner::class)
        ->and($this->registry->all()->count())->toBe(1);
});

test('can get last refiner from registry', function () {
    $this->registry
        ->push(new TestModelRefiner)
        ->push(new TestModelRefinerWithDefaultSort);

    expect(get_class($this->registry->pop()))->toBe(TestModelRefinerWithDefaultSort::class)
        ->and($this->registry->all()->count())->toBe(1);
});

test('can get specific refiner from registry', function () {
    $this->registry
        ->push(new TestModelRefiner)
        ->push(new TestModelRefinerWithDefaultSort)
        ->push(new TestModelRefinerWithSearchAlwaysRan);

    expect(get_class($this->registry->shift(TestModelRefinerWithDefaultSort::class)))->toBe(TestModelRefinerWithDefaultSort::class)
        ->and($this->registry->all()->count())->toBe(2);
});

test('can get specific refiner by model from registry', function () {
    $this->registry
        ->push(new TestModelRefiner)
        ->push(new TestModelRefinerWithDefaultSort, TestModel::class)
        ->push(new TestModelRefiner);

    expect(get_class($this->registry->shift(model: TestModel::class)))->toBe(TestModelRefinerWithDefaultSort::class)
        ->and($this->registry->all()->count())->toBe(2);
});

test('can get first refiner by refiner & model from registry', function () {
    $this->registry
        ->push(new TestModelRefiner)
        ->push(new TestModelRefinerWithDefaultSort, TestModel::class)
        ->push(new TestModelRefinerWithDefaultSort, TestModel::class);

    $expected = get_class($this->registry->shift(TestModelRefinerWithDefaultSort::class, TestModel::class));
    expect($expected)->toBe(TestModelRefinerWithDefaultSort::class)
        ->and($this->registry->all()->count())->toBe(2)
        ->and($this->registry->all()->keys()->all())->toBe([0, 2]);
});

test('can get last refiner by refiner & model from registry', function () {
    $this->registry
        ->push(new TestModelRefiner)
        ->push(new TestModelRefinerWithDefaultSort, TestModel::class)
        ->push(new TestModelRefinerWithDefaultSort, TestModel::class);

    $expected = get_class($this->registry->pop(TestModelRefinerWithDefaultSort::class, TestModel::class));
    expect($expected)->toBe(TestModelRefinerWithDefaultSort::class)
        ->and($this->registry->all()->count())->toBe(2)
        ->and($this->registry->all()->keys()->all())->toBe([0, 1]);
});

test('can retrieve refiner after calling refine on model', function () {
    TestModel::refine();

    expect(get_class(RefinerRegistryFacade::shift()))->toBe(TestModelRefiner::class);
});

test('refiner is not registered if a refiner is specified', function () {
    TestModel::refine(new TestModelRefiner);

    expect(RefinerRegistryFacade::all()->isEmpty())->toBeTrue();
});
