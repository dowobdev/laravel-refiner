<?php

use Dowob\Refiner\Enums\Sort;
use Dowob\Refiner\Tests\TestClasses\Models\TestModel;
use Dowob\Refiner\Tests\TestClasses\Refiners\TestModelRefiner;
use Dowob\Refiner\Tests\TestClasses\Refiners\TestModelRefinerWithDefaultSort;

beforeEach(function () {
    config(['refiner.namespace' => '\\Dowob\\Refiner\\Tests\\TestClasses\\Refiners']);
});

test('does not change query if no sort key', function () {
    $expected = TestModel::query()->toSql();

    expect(TestModel::refine()->toSql())->toBe($expected);
});

test('does not change query if no sort parameters in sort key', function () {
    $expected = TestModel::query()->toSql();

    expect(TestModel::refine(request: createSortRequest([]))->toSql())->toBe($expected);
});

test('non-existent sorts are discarded', function () {
    $expected = TestModel::query()->toSql();

    expect(TestModel::refine(request: createSortRequest('unknown', 'asc'))->toSql())->toBe($expected);
});

test('definitions without sorting not enabled are not added to sort', function () {
    $expected = TestModel::query()->toSql();

    expect(TestModel::refine(request: createSortRequest('user', 'asc'))->toSql())->toBe($expected);
});

test('invalid sort directions are discarded', function () {
    $expected = TestModel::query()->toSql();

    expect(TestModel::refine(request: createSortRequest('name', 'invalid'))->toSql())->toBe($expected);
});

test('can sort by name', function () {
    $expected = TestModel::orderBy('name', 'asc')->toSql();

    expect(TestModel::refine(request: createSortRequest('name', 'asc'))->toSql())->toBe($expected);
});

test('sort converts name to column', function () {
    $expected = TestModel::orderBy('created_at', 'asc')->toSql();

    expect(TestModel::refine(request: createSortRequest('created', 'asc'))->toSql())->toBe($expected);
});

test('can handle multiple sorts and maintain their order', function () {
    $expected = TestModel::orderBy('name', 'desc')->orderBy('created_at', 'asc')->toSql();

    expect(TestModel::refine(request: createSortRequest(['name' => 'desc', 'created' => 'asc']))->toSql())->toBe($expected);
});

test('uses default sort when no sort in query', function () {
    $expected = TestModel::orderBy('name', 'desc')->toSql();

    expect(TestModel::refine(new TestModelRefinerWithDefaultSort, createSortRequest([]))->toSql())->toBe($expected);
});

test('can check if sort is present and get the direction for it', function () {
    TestModel::refine($refiner = new TestModelRefiner, createSortRequest('name', Sort::DESC));

    expect($refiner->hasSort('name'))->toBeTrue()
        ->and($refiner->getSortDirection('name'))->toBe(Sort::DESC)
        ->and($refiner->hasSort('invalid'))->toBeFalse()
        ->and($refiner->getSortDirection('invalid'))->toBeNull();
});

test('can get inverse sort direction', function () {
    TestModel::refine($refiner = new TestModelRefiner, createSortRequest('name', Sort::DESC));
    expect($refiner->getInverseSortDirection('name'))->toBe(Sort::ASC);
    
    TestModel::refine($refiner = new TestModelRefiner, createSortRequest('name', Sort::ASC));
    expect($refiner->getInverseSortDirection('name'))->toBe(Sort::DESC);
});
