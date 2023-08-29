<?php

use Dowob\Refiner\Tests\TestClasses\Models\TestModel;
use Dowob\Refiner\Tests\TestClasses\Refiners\TestModelRefiner;
use Dowob\Refiner\Tests\TestClasses\Refiners\TestModelRefinerWithSearchAlwaysRan;
use Dowob\Refiner\Tests\TestClasses\Refiners\TestModelValidatedRefiner;

beforeEach(function () {
    config(['refiner.namespace' => '\\Dowob\\Refiner\\Tests\\TestClasses\\Refiners']);
});

test('does not change query if no search key', function () {
    $expected = TestModel::query()->toSql();
    $query = TestModel::refine();

    expect($query->toSql())->toBe($expected)
        ->and($query->getBindings())->toBeEmpty();
});

test('does not change query if no search parameters in search key', function () {
    $expected = TestModel::query()->toSql();
    $query = TestModel::refine(request: createSearchRequest([]));

    expect($query->toSql())->toBe($expected)
        ->and($query->getBindings())->toBeEmpty();
});

test('default search is applied when not present in search', function () {
    $expected = TestModel::where('name', 'Default')->toSql();
    $query = TestModel::refine(new TestModelRefinerWithSearchAlwaysRan);

    expect($query->toSql())->toBe($expected)
        ->and($query->getBindings())->toBe(['Default']);
});

test('default search is not applied when search has been specified', function () {
    $expected = TestModel::where('name', 'Not Default')->toSql();
    $query = TestModel::refine(new TestModelRefinerWithSearchAlwaysRan, createSearchRequest('name', 'Not Default'));

    expect($query->toSql())->toBe($expected)
        ->and($query->getBindings())->toBe(['Not Default']);
});

test('discards multi-value searches when not enabled for a definition', function () {
    $expected = TestModel::query()->toSql();
    $query = TestModel::refine(request: createSearchRequest('name', ['Alan', 'Bob']));

    expect($query->toSql())->toBe($expected)
        ->and($query->getBindings())->toBeEmpty();
});

test('discards multi-value searches when not enabled for a definition even if validation allows it', function () {
    $expected = TestModel::query()->toSql();
    $query = TestModel::refine(
        new TestModelValidatedRefiner,
        createSearchRequest('name', ['Alan', 'Bob'])
    );

    expect($query->toSql())->toBe($expected)
        ->and($query->getBindings())->toBeEmpty();
});

test('can check if search is present and get the value for it', function () {
    TestModel::refine($refiner = new TestModelRefiner, createSearchRequest('name', 'Test'));

    expect($refiner->hasSearch('name'))->toBeTrue()
        ->and($refiner->getSearchValue('name'))->toBe('Test')
        ->and($refiner->hasSearch('invalid'))->toBeFalse()
        ->and($refiner->getSearchValue('invalid'))->toBeNull();
});

test('search value is trimmed if trimming is enabled', function () {
    TestModel::refine($refiner = new TestModelRefiner, createSearchRequest('name', '  Test  '));

    expect($refiner->hasSearch('name'))->toBeTrue()
        ->and($refiner->getSearchValue('name'))->toBe('Test');
});

test('search value is not trimmed if trimming is disabled', function () {
    TestModel::refine($refiner = new TestModelRefiner, createSearchRequest('name-no-trim', '  Test  '));

    expect($refiner->hasSearch('name-no-trim'))->toBeTrue()
        ->and($refiner->getSearchValue('name-no-trim'))->toBe('  Test  ');
});

test('can specify custom validation rules', function () {
    TestModel::refine($refiner = new TestModelValidatedRefiner, createSearchRequest([
        'string1'      => 'test1',
        'string2'      => 'test2',
        'integer-min5' => 10,
        'field1'       => 'value1',
        'field2'       => 'value2',
    ]));

    expect($refiner->getSearchValue('string1'))->toBe('test1')
        ->and($refiner->getSearchValue('string2'))->toBe('test2')
        ->and($refiner->getSearchValue('integer-min5'))->toBe(10)
        ->and($refiner->getSearchValue('multiple'))->toBe(['field1' => 'value1', 'field2' => 'value2']);
});

/*
 * Tests for search filters
 */
test('can search by normal equal comparison', function () {
    $expected = TestModel::where('name', 'Test')->toSql();
    $query = TestModel::refine(request: createSearchRequest('name', 'Test'));

    expect($query->toSql())->toBe($expected)
        ->and($query->getBindings())->toBe(['Test']);
});

test('can search using LIKE based filters', function () {
    // LIKE on both sides
    $expected = TestModel::where('like-both', 'like', '%Test%')->toSql();
    $query = TestModel::refine(request: createSearchRequest('like-both', 'Test'));

    expect($query->toSql())->toBe($expected)
        ->and($query->getBindings())->toBe(['%Test%']);

    // LIKE only end
    $expected = TestModel::where('like-end', 'like', 'Test%')->toSql();
    $query = TestModel::refine(request: createSearchRequest('like-end', 'Test'));

    expect($query->toSql())->toBe($expected)
        ->and($query->getBindings())->toBe(['Test%']);

    // LIKE only start
    $expected = TestModel::where('like-start', 'like', '%Test')->toSql();
    $query = TestModel::refine(request: createSearchRequest('like-start', 'Test'));

    expect($query->toSql())->toBe($expected)
        ->and($query->getBindings())->toBe(['%Test']);
});

test('can search by multiple values if enabled', function () {
    $expected = TestModel::whereIn('id', [1, 3])->toSql();
    $query = TestModel::refine(request: createSearchRequest('user', [1, 3]));

    expect($query->toSql())->toBe($expected)
        ->and($query->getBindings())->toBe([1, 3]);
});

test('can search using custom search action on definition', function () {
    $domain = 'example.com';
    $expected = TestModel::where('email', 'like', $binding = '%@' . $domain)->toSql();
    $query = TestModel::refine(request: createSearchRequest('email-domain', $domain));

    expect($query->toSql())->toBe($expected)
        ->and($query->getBindings())->toBe([$binding]);
});

test('custom search works with model scopes set as custom searches', function () {
    $expected = TestModel::query()
        ->where(function ($query) {
            $query->where('name', 'like', '%test%')
                ->orWhere('email', 'like', '%test%');
        })
        ->toSql();
    $query = TestModel::refine(request: createSearchRequest('name-or-email', 'test'));

    expect($query->toSql())->toBe($expected)
        ->and($query->getBindings())->toBe(['%test%', '%test%']);
});
