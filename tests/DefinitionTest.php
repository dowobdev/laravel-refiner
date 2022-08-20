<?php

use Dowob\Refiner\Definitions\Definition;

test('cannot get search filter if it has not been defined', function () {
    $definition = Definition::make('test');

    expect(fn() => $definition->getSearchFilterClass())
        ->toThrow(BadMethodCallException::class);
});

test('cannot specify incorrect LIKE option for LIKE search', function () {
    expect(fn() => Definition::make('test')->searchLike('invalid'))
        ->toThrow(InvalidArgumentException::class);
});

test('can pass no validate rules and auto-generate a required rule', function () {
    $definition = Definition::make('test')->search();
    expect($definition->getValidationRules())
        ->toBe([
            'test' => ['required'],
        ]);
});

test('can pass no validate rules and auto-generate a nullable rule if set to always run', function () {
    $definition = Definition::make('test')->search()->alwaysRun();
    expect($definition->getValidationRules())
        ->toBe([
            'test' => ['nullable'],
        ]);
});

test('can pass no validate rules and auto-generate a required array rule if using multi-search', function () {
    $definition = Definition::make('test')->searchIn();
    expect($definition->getValidationRules())
        ->toBe([
            'test' => ['required', 'array'],
        ]);
});

test('can pass validation rules as a string for one item to be validated', function () {
    $definition = Definition::make('test')->search()->validation($expected = 'required|string');
    expect($definition->getValidationRules())
        ->toBe([
            'test' => $expected,
        ]);
});

test('can pass validation rules as an array for one item to be validated', function () {
    $definition = Definition::make('test')->search()->validation($expected = ['required', 'string']);
    expect($definition->getValidationRules())
        ->toBe([
            'test' => $expected,
        ]);
});

test('can pass validation rules as an array with key specified for one item to be validated', function () {
    $definition = Definition::make('test')->search()->validation($expected = ['test' => ['required', 'string']]);
    expect($definition->getValidationRules())->toBe($expected);
});

test('can pass in multiple validation rule', function () {
    $definition = Definition::make('test')->search()
        ->validation($expected = ['test' => ['required', 'string'], 'test2' => ['nullable']]);
    expect($definition->getValidationRules())->toBe($expected);
});
