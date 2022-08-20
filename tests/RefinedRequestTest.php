<?php

use Dowob\Refiner\Definitions\Definition;
use Dowob\Refiner\Enums\Sort;
use Dowob\Refiner\RefinedRequest;
use Dowob\Refiner\Tests\TestClasses\Models\TestModel;
use Dowob\Refiner\Tests\TestClasses\Refiners\TestModelRefiner;
use Illuminate\Http\Request;

test('exception is thrown if called before refining', function () {
    $refiner = new TestModelRefiner;
    expect(fn() => $refiner->query())->toThrow(BadMethodCallException::class);
});

test('can access query from refiner', function () {
    TestModel::refine($refiner = new TestModelRefiner);
    expect($refiner->query())->toBeEmpty();
});

test('returns empty array if no validated parameters from request', function () {
    $request = new RefinedRequest(new Request, [
        Definition::make('name')->search(),
    ]);

    expect($request->query())->toBeEmpty();
});

test('returns parameters if specified as part of definition', function () {
    $request = new RefinedRequest(
        new Request(
            $query = [
                'search' => [
                    'name'        => 'hello',
                    'multi-value' => [1, 2, 3],
                ],
                'sort'   => ['name' => Sort::ASC],
            ]),
        [
            Definition::make('name')->search()->sort(),
            Definition::make('multi-value')->searchIn(),
        ]
    );

    expect($request->query())->toBe($query);
});

test('sort parameter order is maintained', function () {
    $request = new RefinedRequest(
        new Request(
            $query = [
                'sort' => [
                    // Not alphabetically sorted, order should be maintained.
                    'one'   => Sort::ASC,
                    'two'   => Sort::DESC,
                    'three' => Sort::ASC,
                    'four'  => Sort::ASC,
                ],
            ]),
        [
            Definition::make('one')->sort(),
            Definition::make('two')->sort(),
            Definition::make('three')->sort(),
            Definition::make('four')->sort(),
        ]
    );

    expect($request->query())->toBe($query);
});
